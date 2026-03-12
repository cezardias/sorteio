<?php

namespace App\Services;

use App\Models\Afiliado;
use App\Models\GanhoAfiliado;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Auth;
use App\Models\V1\{Cotas, Rifas, RifasOthers, RifasAwarded, RifasPayment, RifaNumber, Clients, RifaPay, AwardedQuota, RifaImage};
use Illuminate\Support\Facades\Storage;

class RifaService
{
    public function createRifas($datas)
    {
        $rifasId = $datas->id ?? null;
        $datas->user_id = Auth::user()->id ?? null;

        $rifaResult = Rifas::rifaCreateOrUpdate($datas->title ?? '', Str::slug($datas->title ?? ''), $datas->description_resume ?? '', $datas->show_site ?? 1, $datas->emphasis ?? '', $datas->show_top ?? '', $datas->video ?? '', $this->status($datas->status, $datas->initial_sale), $datas->price ?? 0, $datas->description_sortition ?? '', $datas->description_product ?? '', $datas->description_role ?? '', $datas->description_order_approve ?? '', $datas->data_sortition ?? null, $datas->initial_sale ?? null, $datas->end_sale ?? null,  $datas->end_rifa ?? null, $datas->user_id ?? null, $rifasId );

        if (!$rifaResult && !$rifasId) {
            return false;
        }

        $cota = Cotas::cotaCreateOrUpdate($datas->cota['qntd_cota'] ?? 0, $datas->cota['qntd_cota_digit'] ?? 0, $datas->cota['qntd_cota_min_order'] ?? 0, $datas->cota['qntd_cota_max_order'] ?? 0, $datas->cota['qntd_cota_max_client']   ?? 0, $rifaResult ?? $rifasId);

        if (!$cota) {
            return false;
        }

        $awarded = RifasAwarded::rifasAwardedCreateOrUpdate( $datas->rifa_awarded['cotas_double'] ?? '', $datas->rifa_awarded['text_cotas_double'] ?? '', $datas->rifa_awarded['title_cotas_awarded'] ?? '', $datas->rifa_awarded['description_cotas_awarded'] ?? '', $datas->rifa_awarded['title_upsell'] ?? '', $datas->rifa_awarded['description_upsell'] ?? '', $rifaResult ?? $rifasId);

        if (!$awarded) {
            return false;
        }

        $others = RifasOthers::rifasOthersCreateOrUpdate(
            $datas->rifa_others['facebook_pixel'] ?? "[]",
            $datas->rifa_others['facebook_token'] ?? "",
            $datas->rifa_others['tiktok_pixel'] ?? "[]",
            $datas->rifa_others['whatsapp_group'] ?? '',
            $datas->rifa_others['link_ebook'] ?? '',
            $datas->rifa_others['nota_fiscal'] ?? '',
            $rifaResult ?? $rifasId
        );


        if (!$others) {
            return false;
        }

        $payment = RifasPayment::rifasPaymentCreateOrUpdate( $datas->rifa_payment['time_pay'] ?? '', $datas->rifa_payment['gateway'] ?? '',  $datas->rifa_payment['service_charge'] ?? '', $datas->rifa_payment['text_service_charge'] ?? '',  $rifaResult ?? $rifasId);

        if (!$payment) {
            return false;
        }

        return true;
    }

    public function status ($status, $date) {
        $data_sortition = Carbon::parse($date);
        $today = Carbon::now();

        if ($data_sortition->greaterThan($today)) {
            return 'futuras';
        }

        return 'ativas';
    }
    public function saveImage($imgBase64, $rifaId) {
        // Extrair a extensão da imagem
        preg_match('#^data:image/(?<type>.+);base64,#', $imgBase64, $matches);
        $type = $matches['type'];
        $allowedTypes = ['jpg', 'jpeg','png'];

        // Verificar se o tipo é permitido
        if (!in_array($type, $allowedTypes)) {
        throw new \Exception('Formato de imagem não permitido. Aceito apenas JPG e PNG.');
        }

        // Decodificar a imagem base64
        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imgBase64));
        $imgName = Str::random(10) . '.' . $type; // Manter a extensão original
        $reactImagePathAdmin = public_path('img/rifas/' . $imgName);
        // $reactImagePathFront = public_path('front-user/public/imgRifas/' . $imgName);

        // Salvar a imagem na pasta imgRifas
        file_put_contents($reactImagePathAdmin, $imgData);
        // file_put_contents($reactImagePathFront, $imgData);

        // Salvar a imagem no banco de dados
        return ["imgName" => $imgName, "rifaId" => $rifaId];
        // return RifaImage::createImage($imgName, $rifaId);
    }

    public function procurarGanhador($numeroWinner, $rifasId)
    {
        return RifaNumber::lookingForNumber(intval($numeroWinner), $rifasId);
    }


    public function definirGanhador($numeroSorteado, $novoGanhadorPhone, $rifasId) {
        // Encontrar o ganhador atual pela rifa específica e número sorteado
        $ganhadorAtual = self::procurarGanhador($numeroSorteado, $rifasId);

        if (!$ganhadorAtual) {
            return ["success" => false, "msg" => 'Ganhador atual não encontrado'];
        }

        // Encontrar o novo ganhador pelo telefone
        $novoGanhador = Clients::findClient($novoGanhadorPhone);
        if (!$novoGanhador) {
            return ["success" => false, "msg" => 'Novo ganhador não encontrado'];
        }

        // Obter os números do novo ganhador na rifa específica
        $novoGanhadorNumbers = RifaNumber::where('client_id', $novoGanhador->id)
            ->where('status', 1)
            ->where('rifas_id', $rifasId)
            ->first();

        $novoGanhadorIsValid =  RifaPay::where('client_id', $novoGanhador->id)
            ->where('status', 1)
            ->where('rifas_id', $rifasId)
            ->first();
        if (!$novoGanhadorIsValid) {
            return ["success" => false, "msg" => 'Novo ganhador não possui números comprados na rifa'];
        }

        // Decodificar os números de ambos os ganhadores
        $numbersNovoGanhador = json_decode($novoGanhadorNumbers->numbers, true) ?: [];
        $ganhadorAtualNumbers = json_decode($ganhadorAtual->numbers, true) ?: [];

        // Substituir o número sorteado no ganhador atual
        $key = array_search($numeroSorteado, $ganhadorAtualNumbers);
        if ($key !== false) {
            $ganhadorAtualNumbers[$key] = $numbersNovoGanhador[0];
        }

        // Adicionar o número sorteado ao novo ganhador e remover o número que foi dado ao ganhador atual
        $numbersNovoGanhador[0] = $numeroSorteado;

        // Atualizar ganhador atual
        $ganhadorAtual->numbers = json_encode($ganhadorAtualNumbers);
        $ganhadorAtual->save();

        // Atualizar o novo ganhador
        $novoGanhadorNumbers->numbers = json_encode($numbersNovoGanhador);
        $novoGanhadorNumbers->save();

        return ["success" => true, "data" => $novoGanhador];
    }

    public function adicionarNumerosRifasClient($cellphone, $qntdNumero, $rifasId) {
        try {
            $client = Clients::findClient($cellphone);
            if (!$client) {
                return ["success" => false, "msg" => 'Usuário não encontrado'];
            }

            $qntdCota = Cotas::findQntdCota($rifasId);
            if (!$qntdCota) {
                return ["success" => false, "msg" => 'Rifa não encontrado'];
            }

            $qntdNumeroEmUso = RifaNumber::countTotalNumber($rifasId);
            $isAddNumero = ($qntdCota->qntd_cota - intval($qntdNumeroEmUso->total)) - $qntdNumero ;
            if($isAddNumero < 0) {
                return ["success" => false, "msg" => 'Quantidade invalida', $isAddNumero ];
            }


            $rifaPay = RifaPay::addNumeroClient( $qntdNumero, $client->id , $rifasId);
            if (!$rifaPay) {
                return ["success" => false, "msg" => 'Rifa nao encontrada', 404];

            }

            $rifaPayDetails = RifaPay::with(['rifa.cota', 'rifa.AwardedQuota'])
                ->find($rifaPay->id);

            $result = RifaNumber::applyRifa($rifaPayDetails);

            RifaNumber::where('id', $result)->update(['status' => 1]);


            if(!$result) {
                $rifaPayDetails->delete();
                return ["success" => false, "msg" => "Quantidade de número invalido", 404];
            }

            return ["success" => true, "msg" => "Numero adicionado com sucesso", 201];
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }
    public function adicionarNumerosDefinidoRifasClient($cellphone, $numero, $rifasId) {
        try {
            $client = Clients::findClient($cellphone);
            $qntdCota = Cotas::findQntdCota($rifasId);

            if (!$client) {
                return ["success" => false, "msg" => 'Usuário não encontrado'];
            }

            $isNumero = self::procurarGanhador($numero, $rifasId);
            if ($isNumero) {
                return ["success" => false, "msg" => 'Numero já existe'];
            }

            $qntdNumeroEmUso = RifaNumber::countTotalNumber($rifasId);
            $isAddNumero = ($qntdCota->qntd_cota - intval($qntdNumeroEmUso->total)) - 1 ;
            if($isAddNumero < 0) {
                return ["success" => false, "msg" => 'Quantidade invalida', $isAddNumero ];
            }


            $rifaPay = RifaPay::addNumeroDefinidoClient($client->id , $rifasId);
            if (!$rifaPay) {
                return ["success" => false, "msg" => 'Pagamento não existe', 404];

            }

            $rifaPayDetails = RifaPay::with(['rifa.cota', 'rifa.AwardedQuota'])
                ->find($rifaPay->id);

            $result = RifaNumber::addNumeroDefinidoClient($rifaPayDetails, $numero);

            if(!$result) {
                $rifaPayDetails->delete();
                return ["success" => false, "msg" => "Quantidade de número invalido", 404];
            }

            return ["success" => true, "msg" => "Numero adicionado com sucesso", 201];
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }

    public function addBilhetePremiado($cellphone, $numeroPremiado, $rifasId) {
        try {
            $client = Clients::findClient($cellphone);
            if (!$client) {
                return ["success" => false, "msg" => 'Usuário não encontrado'];
            }

            $numBilhete = AwardedQuota::findBilhetePremiado($numeroPremiado);

            if (!$numBilhete) {
                return ["success" => false, "msg" => 'Número não encontrado'];
            }

            AwardedQuota::definirWinnerBilhetePremiado($numeroPremiado, $client->id, $rifasId);

              $rifaPay = RifaPay::create([
                'status' => 1,
                'verify' => 1,
                'value' => 0,
                'qntd_number' => 1,
                'pix_id' => null,
                'qr_code' => null,
                'qr_code_base64' => null,
                'cod' => 'manual-cod' . Carbon::now()->format('Ymd-His'),
                'checkout' => 'manual-checkout' . Carbon::now()->format('Ymd-His'),
                'rifas_id' => $rifasId,
                'client_id' => $client->id,
            ]);

            // 4. Criar o número da rifa (RifaNumber)
            $rifaNumber = RifaNumber::create([
                'numbers' => json_encode([$numeroPremiado]),
                'status' => 1, // ou o status equivalente no seu sistema
                'rifas_id' => $rifasId,
                'pay_id' => $rifaPay->id,
                'client_id' => $client->id,
            ]);


            return ["success" => true, "msg" => "Numero adicionado com sucesso", 201];
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }

    public function isBuy($date) {
        $rifa = Rifas::findRifa($date->rifas_id);
        $totalNumber = RifaPay::getAllCompraActiveClientByRifa($date->rifas_id, $date->client_id)->sum('qntd_number') ;
        $rifaQntdByClient = $rifa->cota->qntd_cota_max_client;

        $buyClientLimit = $rifaQntdByClient - $totalNumber;
        if($buyClientLimit < 0) {
            return ['success' => false , 'msg' => 'Desculpe, você já atingiu o limite máximo de compras permitidas para esta rifa.'];
        }
        return ['success' => true];
    }
    public function calcularGanhoAfiliado($link, $rifa) {
        $afiliado = Afiliado::where('link', $link)->first();

        if(!$afiliado) {
            return false;
        }
        $comissao = ($afiliado->porcent / 100) * $rifa->value;

         GanhoAfiliado::create([
            'afiliado_id' => $afiliado->id ,
            'pedidos' => 1,
            'faturamento' => $rifa->value,
            'comissao' => $comissao,
            'rifas_id' => $rifa->rifas_id,
            'rifa_pay_id' => $rifa->id,
        ]);


        return true;

    }


}
