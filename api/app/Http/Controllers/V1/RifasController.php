<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\RifaNumber;

use \Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Requests\V1\{StoreRifasRequest, UpdateRifasRequest, PaymentRequest, OrderRifaRequest, WinnerRequest};
use App\Http\Resources\V1\{RifasResource};
use App\Models\V1\{Rifas, RifaWinner, RifaPay, AwardedQuota, DiscountPackage, RifaUpsell, RifaImage, Cotas, RifasAwarded, RifasOthers, RifasPayment};
use App\Services\{RifaService, MercadoPagoService, Pay2MService, RewardEngineService, EscaleCyberService};
use Illuminate\Support\Facades\Validator;






class RifasController extends Controller
{

    protected $success = 200;
    protected $postSuccess = 201;
    protected $notFound = 404;
    protected $serverError = 500;

    private RifaService $rifaService;
    private MercadoPagoService $mercadoPagoService;
    private Pay2MService $pay2MService;
    private EscaleCyberService $escaleCyberService;

    public function __construct(RifaService $rifaService, MercadoPagoService $mercadoPagoService, Pay2MService $pay2MService, EscaleCyberService $escaleCyberService)
    {
        $this->rifaService = $rifaService;
        $this->mercadoPagoService = $mercadoPagoService;
        $this->pay2MService = $pay2MService;
        $this->escaleCyberService = $escaleCyberService;
    }
    public function index()
    {
        try {
            $rifasData = Rifas::getAllRifasActivas()->where('emphasis', 'sim')->first();

            return response()->json(["success" => true, "data" => $rifasData], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }

    public function getAllRifasAdmin(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            // Consulta base apenas com dados essenciais
            $rifas = Rifas::select('id', 'title', 'slug', 'status', 'data_sortition', 'created_at')
                ->with([
                    'cota:id,rifas_id,qntd_cota',
                    'rifaImage:id,rifas_id,path',
                ])
                ->orderByDesc('created_at')
                ->paginate($perPage);

            // Agora vamos montar os dados adicionais no PHP
            $rifas->getCollection()->transform(function ($rifa) {

                // Faturamento total
                $fatTotal = $rifa->rifaPay()->where('status', 1)->sum('value');

                // Faturamento de hoje
                $fatHoje = $rifa->rifaPay()
                    ->where('status', 1)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('value');

                // Quantidade de cotas vendidas (ativo)
                $qntdNumeros = $rifa->rifaNumberActive()->pluck('numbers')->flatMap(function ($str) {
                    return explode(',', $str);
                })->filter()->count();

                // Quantidade de cotas reservadas
                $qntdNumerosReservado = $rifa->rifaNumberReservado()->pluck('numbers')->flatMap(function ($str) {
                    return explode(',', $str);
                })->filter()->count();

                // Adiciona ao objeto
                $rifa->fat_total = $fatTotal;
                $rifa->fat_hoje = $fatHoje;
                $rifa->qntd_numeros = $qntdNumeros;
                $rifa->qntd_numeros_reservado = $qntdNumerosReservado;

                return $rifa;
            });

            return response()->json([
                'success' => true,
                'data' => $rifas->items() // <- devolve só o array da paginação
            ], 200);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }


    public function getAllRifasAdminFiltro(Request $request)
    {
        try {
            $query = Rifas::with(['cota', 'rifaImage'])
                ->withSum('rifaPayActiva as fat_total', 'value')
                ->withSum('rifaPayToday as fat_hoje', 'value')
                ->withCount([
                    'rifaNumberActive as qntd_numeros' => function ($query) {
                        $query->select(DB::raw("SUM(LENGTH(numbers) - LENGTH(REPLACE(numbers, ',', '')) + 1) as number_count"));
                    }
                ])
                ->withCount([
                    'rifaNumberReservado as qntd_numeros_reservado' => function ($query) {
                        $query->select(DB::raw("SUM(LENGTH(numbers) - LENGTH(REPLACE(numbers, ',', '')) + 1) as number_count"));
                    }
                ]);

            // Adiciona filtros se os parâmetros estiverem presentes e não estiverem vazios
            if ($request->has('title') && $request->input('title') != '') {
                $query->where('title', 'like', '%' . $request->input('title') . '%');
            }

            if ($request->has('status') && $request->input('status') != '') {
                $query->where('status', $request->input('status'));
            }

            $rifasData = $query->latest()->get();

            // if ($rifasData->isEmpty()) {
            //     return response()->json(["success" => false, "msg" => "Rifas não foram encontradas."], 404);
            // }

            return response()->json(["success" => true, "data" => $rifasData], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }



    public function allRifas()
    {
        try {
            $rifasData = Rifas::getAllRifas();

            if (!$rifasData) {
                return response()->json(["success" => false, "msg" => "rifas have not been found."], $this->notFound);
            }

            return response()->json(["success" => true, "data" => $rifasData], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }


    public function storeRifa(StoreRifasRequest $request)
    {

        try {
            // $isMakeRifa = Rifas::RifaActiva();

            // if($isMakeRifa) {
            //     return response()->json(["success" => true, "msg" => "Para cadastrar um nova rifa, você precisa finalizar a rifa: $isMakeRifa->title"], 422);
            // }
            $rifa = $this->rifaService->createRifas($request);

            if (!$rifa) {
                return response()->json(["success" => true, "msg" => "Ocorreu um erro ao cadastrar"], 422);
            }
            return response()->json(["success" => true, "msg" => "Rifa criada com sucesso"], $this->success);
        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], $this->serverError);
        }
    }

    public function getOneRifa($id)
    {
        try {
            $rifasData = Rifas::getOneRifa($id);
            if (!$rifasData) {
                return response()->json(["success" => false, "msg" => "rifas have not been found."], $this->notFound);
            }

            return response()->json(["success" => true, "data" => $rifasData], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }
    public function editRifa($id, Request $request)
    {
        try {
            $rifasData = Rifas::getOneRifa($id);
            if (!$rifasData) {
                return response()->json(["success" => false, "msg" => "Rifa não encontrada"], 404);
            }

            $rifa = $this->rifaService->createRifas($request);
            return response()->json(["success" => true, "msg" => "Rifa editada com sucesso"], $this->success);


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], $this->serverError);
        }
    }
    public function excluirRifa($id)
    {
        try {
            $rifasData = Rifas::getOneRifa($id);
            if (!$rifasData) {
                return response()->json(["success" => false, "msg" => "Rifa não encontrada"], 404);
            }

            // Delete all related records
            Cotas::where('rifas_id', $id)->delete();
            DiscountPackage::where('rifas_id', $id)->delete();
            RifaNumber::where('rifas_id', $id)->delete();
            RifaPay::where('rifas_id', $id)->delete();
            RifasAwarded::where('rifas_id', $id)->delete();
            RifasOthers::where('rifas_id', $id)->delete();
            RifasPayment::where('rifas_id', $id)->delete();
            AwardedQuota::where('rifas_id', $id)->delete();
            RifaImage::where('rifas_id', $id)->delete();

            // Agora exclua a rifa
            $rifasData->delete();

            return response()->json(["success" => true, "msg" => "Rifa excluida com sucesso"], 200);
        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao excluir a rifa", "error" => $e->getMessage()], 500);
        }
    }



    public function storeBilhetePremiado(Request $request)
    {
        try {
            $qntdCota = $request->qntd_cota;
            $rifaId = $request->rifas_id;

            $rifa = Rifas::with('cota')->find($rifaId);
            if (!$rifa) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrada'], 404);
            }

            $qntdCotaExist = AwardedQuota::where('rifas_id', $rifaId)->where('number_cota', $request->qntd_cota)->first();

            if (!$qntdCotaExist) {
                $bilhetePremiado = AwardedQuota::createAwardedQuota($qntdCota, $request->award, $request->show_site, $request->status, $rifaId);

                if ($bilhetePremiado) {

                    $bilhetePremiado = AwardedQuota::getAllBilhetePremiado($rifaId);
                    return response()->json(["success" => true, "msg" => "Bilhete criada com sucesso", 'data' => $bilhetePremiado], 200);

                }

                return response()->json(['response' => false, 'msg' => 'Erro ao criar a cota'], 500);
            }

            return response()->json(['response' => false, 'msg' => 'Número já inserido'], 400);
        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }
    public function editarBilhetePremiado(Request $request)
    {
        try {
            $bilheteId = $request->id;
            $rifaId = $request->rifas_id;

            $bilhete = AwardedQuota::find($bilheteId);

            if (!$bilhete) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrada'], 404);
            }

            $bilhetePremiado = AwardedQuota::updateAwardedQuota($request);
            if ($bilhetePremiado) {
                $bilhetePremiadoAll = AwardedQuota::getAllBilhetePremiado($rifaId);
                return response()->json(["success" => true, "msg" => "Bilhete Premiado criada com sucesso", 'data' => $bilhetePremiadoAll], 200);
            }


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao editar a bilhete", "error" => $e->getMessage()], 500);
        }
    }
    public function destroyBilhetePremiado(Request $request)
    {
        try {
            $bilheteId = $request->id;
            $rifaId = $request->rifaId;

            $bilhete = AwardedQuota::find($bilheteId);

            if (!$bilhete) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrada'], 404);
            }

            $isDelete = $bilhete->delete();
            if ($isDelete) {
                $bilhetePremiadoAll = AwardedQuota::getAllBilhetePremiado($rifaId);
                return response()->json(["success" => true, "msg" => "Bilhete Premiado criada com sucesso", 'data' => $bilhetePremiadoAll], 200);
            }


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao editar a bilhete", "error" => $e->getMessage()], 500);
        }
    }
    public function getAllBilhetePremiado($id)
    {
        try {
            $nomeRifa = Rifas::where('id', $id)->first();

            $rifa = Rifas::find($id);
            if ($rifa == []) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrada'], 404);
            }

            $qntdCotaExist = AwardedQuota::getAllBilhetePremiado($id);
            if ($qntdCotaExist->isEmpty()) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrada'], 404);
            }
            return response()->json(["success" => true, "data" => $qntdCotaExist, 'nomeRifa' => $nomeRifa], 200);




        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }
    public function getBilhetePremiadoFiltro(Request $request, $id)
    {
        try {
            $rifa = Rifas::find($id);
            if ($rifa == null) {
                return response()->json(['response' => false, 'msg' => 'Bilhete não encontrado'], 404);
            }

            $qntdCotaExist = AwardedQuota::with('client')->where('rifas_id', $id);

            // Aplica os filtros adicionais
            if ($request->has('status') && $request->input('status') != '') {
                $qntdCotaExist->where('status', $request->input('status'));
            }

            if ($request->has('mostraSite') && $request->input('mostraSite') != '') {
                $qntdCotaExist->where('show_site', $request->input('mostraSite'));
            }

            $results = $qntdCotaExist->paginate(20);

            // Se não houver resultados, retorna todos
            if ($results->isEmpty()) {
                $qntdCotaExist = AwardedQuota::with('client')->where('rifas_id', $id)->paginate(20);
                return response()->json(["success" => true, "data" => $qntdCotaExist], 200);
            }

            return response()->json(["success" => true, "data" => $results], 200);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }



    public function getOneBilhetePremiado($id)
    {
        try {
            $bilhete = AwardedQuota::getOneBilhetePremiado($id);
            if (!$bilhete) {
                return response()->json(['response' => false, 'msg' => 'bilhete não encontrada'], 404);
            }

            return response()->json(["success" => true, "data" => $bilhete], 200);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno", "error" => $e->getMessage()], 500);
        }
    }
    public function storePacote(Request $request)
    {
        try {
            $rifaId = $request->rifas_id;

            $rifa = Rifas::withCount('cota')->find($rifaId);
            if (!$rifa) {
                return response()->json(['response' => false, 'msg' => 'Rifa não encontrada'], 404);
            }

            $pacote = DiscountPackage::createDiscountPackage($request);

            if ($pacote) {
                $pacote = DiscountPackage::getAllPacotes($rifaId);
                return response()->json(["success" => true, "msg" => "Pacote criado com sucesso", 'data' => $pacote], 200);
            }

            return response()->json(['response' => false, 'msg' => 'Erro ao criar o pacote'], 500);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }
    public function editarPacote(Request $request)
    {
        try {
            $rifaId = $request->rifas_id;

            $rifa = Rifas::withCount('cota')->find($rifaId);
            if (!$rifa) {
                return response()->json(['response' => false, 'msg' => 'Rifa não encontrada'], 404);
            }

            $pacote = DiscountPackage::createDiscountPackage($request);

            if ($pacote) {
                $pacote = DiscountPackage::getAllPacotes($rifaId);
                return response()->json(["success" => true, "msg" => "Pacote editado com sucesso", 'data' => $pacote], 200);
            }

            return response()->json(['response' => false, 'msg' => 'Erro ao editar o pacote'], 500);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro", "error" => $e->getMessage()], 500);
        }
    }
    public function deletePacote(Request $request)
    {
        try {

            $packageId = $request->id;

            $package = DiscountPackage::find($packageId);

            if (!$package) {
                return response()->json(['response' => false, 'msg' => 'Pacote não encontrada'], 404);
            }

            $isDelete = $package->delete();
            if ($isDelete) {
                $pacotesAll = DiscountPackage::getAllPacotes($packageId);
                return response()->json(["success" => true, "msg" => "Pacote excluido com sucesso", 'data' => $pacotesAll], 200);
            }

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro", "error" => $e->getMessage()], 500);
        }
    }

    public function getAllPacotes($id)
    {
        try {
            $nomeRifa = Rifas::where('id', $id)->first();
            $pacotes = DiscountPackage::getAllPacotes($id);
            if (!$pacotes) {
                return response()->json(['response' => false, 'msg' => 'Pacote não encontrada'], 404);
            }

            return response()->json(["success" => true, "data" => $pacotes, 'nomeRifa' => $nomeRifa], 200);


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }
    public function getOnePacotes($id)
    {
        try {

            $pacote = DiscountPackage::getOnePacote($id);
            if (!$pacote) {
                return response()->json(['response' => false, 'msg' => 'Pacote não encontrada'], 404);
            }

            return response()->json(["success" => true, "data" => $pacote], 200);


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }
    public function filtroPacotes(Request $request, $id)
    {
        try {
            // Inicializa a query base, filtrando pelo 'rifas_id'
            $query = DiscountPackage::where('rifas_id', $id);

            // Aplica os filtros se forem fornecidos
            if ($request->filled('codPromo')) {
                $query->where('cod_promo', 'like', '%' . $request->input('codPromo') . '%');
            }

            if ($request->filled('maisPopular')) {
                $query->where('popular', $request->input('maisPopular'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Aplica ordenação conforme o filtro
            if ($request->filled('ordem')) {
                switch ($request->input('ordem')) {
                    case 'qtdAsc':
                        $query->orderBy('qntd_cota', 'asc');
                        break;
                    case 'qtdDesc':
                        $query->orderBy('qntd_cota', 'desc');
                        break;
                    case 'faturadoAsc':
                        $query->orderBy('valor_total', 'asc');
                        break;
                    case 'faturadoDesc':
                        $query->orderBy('valor_total', 'desc');
                        break;
                    case 'totalAsc':
                        $query->orderBy('value_cota', 'asc');
                        break;
                    case 'totalDesc':
                        $query->orderBy('value_cota', 'desc');
                        break;
                    case 'created':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'updated':
                        $query->orderBy('updated_at', 'desc');
                        break;
                }
            }

            // Obtém os resultados
            $pacotes = $query->get();

            // Verifica se encontrou algum resultado
            if ($pacotes->isEmpty()) {
                return response()->json(['response' => false, 'msg' => 'Nenhum pacote encontrado com os filtros aplicados'], 404);
            }

            return response()->json(["success" => true, "data" => $pacotes], 200);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao filtrar os pacotes", "error" => $e->getMessage()], 500);
        }
    }



    public function getUpsellRifa($id)
    {
        try {
            $nomeRifa = Rifas::where('id', $id)->first();

            $upsell = RifaUpsell::getAllUpsellRifa($id);

            if (!$upsell) {
                return response()->json(["success" => false, "msg" => "Upsell não encontrado"], 404);
            }

            return response()->json(['response' => true, 'data' => $upsell, 'nomeRifa' => $nomeRifa], 200);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }

    }

    public function storeUpsell(Request $request)
    {
        try {
            $rifaId = $request->rifas_id;

            $rifa = Rifas::withCount('cota')->find($rifaId);
            if (!$rifa) {
                return response()->json(['response' => false, 'msg' => 'Rifa não encontrada'], 404);
            }

            $upsell = RifaUpsell::createUpsell($request->id ?? null, $request->qntd_cota, $request->price_cota, $request->price_total, $request->qntd_min, $request->qntd_max, $request->localizacao, $request->status ?? 'ativo', $rifaId);

            if (!$upsell) {
                return response()->json(['response' => false, 'msg' => 'Erro ao criar o pacote'], 500);

            }
            return response()->json(["success" => true, "msg" => "Pacote criado com sucesso"], 200);

        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno ao cadastrar a rifa", "error" => $e->getMessage()], 500);
        }
    }

    public function storeImagem(Request $request)
    {
        try {
            // Validar a entrada
            $request->validate([
                'imagem' => 'required|string',
                'rifa_id' => 'required|integer',
            ]);

            $imgBase64 = $request->input('imagem');
            $rifaId = $request->input('rifa_id');

            // Usar o serviço para armazenar a imagem
            $resp = $this->rifaService->saveImage($imgBase64, $rifaId);
            $result = RifaImage::createImage($resp['imgName'], $resp['rifaId']);
            return response()->json(["response" => $result, "msg" => $result ? "Imagem salva com sucesso." : "Erro ao salvar imagem."], $result ? 201 : 500);
        } catch (\Exception $e) {
            return response()->json(["response" => false, "msg" => "Erro ao processar a imagem.", "error" => $e->getMessage()], 500);
        }
    }

    public function getImagens($id)
    {
        try {
            $nomeRifa = Rifas::where('id', $id)->first();

            $imagens = RifaImage::getRifaImagens($id);
            if (!count($imagens)) {
                return response()->json(['response' => false, 'msg' => 'Imagem não encontrada'], 404);
            }

            return response()->json(["success" => true, "data" => $imagens, 'nomeRifa' => $nomeRifa], 200);


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno", "error" => $e->getMessage()], 500);
        }
    }
    public function destroyImagem($id)
    {
        try {

            $img = RifaImage::getOneRifaImagens($id);
            if (!$img) {
                return response()->json(['response' => false, 'msg' => 'Imagem não encontrada'], 404);
            }
            $img->delete();
            return response()->json(["success" => true, "msg" => 'Imagem deletada com sucesso'], 200);


        } catch (Exception $e) {
            return response()->json(["response" => false, "msg" => "Ocorreu um erro interno", "error" => $e->getMessage()], 500);
        }
    }

    public function finalizarRifa($id)
    {
        $rifa = Rifas::where('id', $id);
        if (!$rifa) {
            return response()->json(["response" => true, "msg" => "Nenhuma rifa encontrada."], 404);
        }

        $hoje = Carbon::now();
        $rifa->update(['status' => 'finalizadas', 'end_rifa' => $hoje]);
        return response()->json(["response" => true, "msg" => "Rifa finalizada com sucesso."]);
    }
    public function ativarRifa($id)
    {
        $rifa = Rifas::where('id', $id);
        if (!$rifa) {
            return response()->json(["response" => true, "msg" => "Nenhuma rifa encontrada."], 404);
        }


        $rifa->update(['status' => 'ativas', 'end_rifa' => null]);
        return response()->json(["response" => true, "msg" => "Rifa finalizada com sucesso."]);
    }


    public function show($slug, $id)
    {
        try {
            $rifaData = Rifas::getOneRifa($id);

            if (!$rifaData) {
                return response()->json(["success" => false, "msg" => "Rifa has not been found."], $this->notFound);
            }

            // Obter as datas formatadas
            $initialSaleDate = Carbon::parse($rifaData->initial_sale)->toDateString();
            $todayDate = Carbon::today()->toDateString();

            if ($initialSaleDate <= $todayDate && $rifaData->status == 'futuras') {
                Rifas::where('id', $id)->update(['status' => 'ativas']);
            }

            $ranking = RifaNumber::getRankingRifa($id);
            $data = ['rifa' => $rifaData, 'ranking' => $ranking];

            return response()->json(["success" => true, "data" => $data, "aa" => $initialSaleDate, 'aaaaa' => $todayDate], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }
    public function buyRifa(Request $request, $afiliadoId = null)
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer|exists:clients,id',
                'qntd_number' => 'required|integer|min:1',
                'cotas_double' => 'nullable|integer',
                'pacote_promo' => 'nullable',
                'rifas_id' => 'required|exists:rifas,id',
            ]);

            if ($validator->fails()) {
                return response()->json(["success" => false, "msg" => $validator->errors()], 422);
            }

            $isBuyRifa = $this->rifaService->isBuy($request);
            if (!$isBuyRifa['success']) {
                return response()->json(["success" => false, "msg" => $isBuyRifa['msg']], 409);
            }

            $rifa = Rifas::with(['rifaPayment', 'discountPackage'])->findOrFail($request->rifas_id);




            $unitPrice = $rifa->price ?? 0;
            $qntdNumber = (int) $request->qntd_number;

            if ($request->pacote_promo != 0) {
                $discountPackage = $rifa->discountPackage->where('id', $request->pacote_promo)->first();

                if ($discountPackage) {
                    $calculatedValue = $discountPackage->value_cota * $qntdNumber;
                } else {
                    $calculatedValue = $unitPrice * $qntdNumber;
                }
            } else {
                $calculatedValue = $unitPrice * $qntdNumber;
            }

            if ($request->cotas_double == 1) {
                $request->qntd_number = $qntdNumber * 2;
            }

            $calculatedValue = round($calculatedValue, 2);

            $request->merge([
                'value' => $calculatedValue,
                'valorCalculado' => $calculatedValue
            ]);

            $cota = Cotas::where('rifas_id', $request->rifas_id)->first();

            if ($cota) {
                $qntdSolicitada = $request->cotas_double == 1 ? $qntdNumber * 2 : $qntdNumber;

                if ($qntdSolicitada > $cota->qntd_cota_max_order) {
                    return response()->json([
                        "success" => false,
                        "msg" => "Você só pode comprar no máximo {$cota->qntd_cota_max_order} cotas por pedido."
                    ], 422);
                }
            }
            $rifaPay = RifaPay::applyRifa($request);
            if (!$rifaPay) {
                return response()->json(["success" => false, "msg" => "Ocorreu um erro ao registrar a compra"], 500);
            }


            $rifaPayDetails = RifaPay::with(['rifa.cota', 'rifa.AwardedQuota', 'client'])
                ->find($rifaPay->id);

            $result = RifaNumber::applyRifa($rifaPayDetails);

            if (!$result) {
                $rifaPayDetails->delete();
                return response()->json(["success" => false, "msg" => "Quantidade de número invalido"], 409);
            }


            $payment = $this->escaleCyberService->createPixTransaction([
                'amount' => $rifaPayDetails->value,
                'customerName' => $rifaPayDetails->client->name,
                'customerEmail' => $rifaPayDetails->client->email ?? 'cliente@exemplo.com',
                'customerPhone' => $rifaPayDetails->client->phone,
                'customerDocument' => $rifaPayDetails->client->cpf ?? '00000000000',
                'description' => "Compra da rifa " . $rifaPayDetails->rifa->title,
                'metadata' => [
                    'rifa_id' => $rifaPayDetails->rifas_id,
                    'client_id' => $rifaPayDetails->client_id,
                    'rifa_pay_id' => $rifaPayDetails->id
                ]
            ]);

            if (!$payment || !$payment['success']) {
                return response()->json(["success" => false, "msg" => "Erro ao gerar pagamento", "details" => $payment['message'] ?? 'Unknown error'], 500);
            }

            $rifaPayDetails->pixId = $payment['transaction_id'];
            $rifaPayDetails->qrCode = $payment['qr_code'];
            $rifaPayDetails->qrCodeBase64 = $payment['qr_code_base64'];

            RifaPay::addPix($rifaPay->id, $rifaPayDetails->pixId, $rifaPayDetails->qrCode, $rifaPayDetails->qrCodeBase64);

            if ($afiliadoId) {
                $this->rifaService->calcularGanhoAfiliado($afiliadoId, $rifaPay);
            }

            return response()->json(["success" => true, "data" => $rifaPayDetails], 200);


        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function checkPaymentStatus($paymentId)
    {
        $paymentStatus = $this->escaleCyberService->getTransactionStatus($paymentId);

        if (!$paymentStatus['success']) {
            return response()->json([
                'status' => false,
                'message' => 'Falha ao verificar o status do pagamento: ' . ($paymentStatus['message'] ?? 'Erro desconhecido')
            ]);
        }

        $payments = RifaPay::with('client')->where('pix_id', $paymentId)->first();

        if (!$payments) {
            return response()->json([
                'status' => false,
                'message' => 'Pagamento não encontrado ou já processado.'
            ]);
        }

        $checkPaymentStatus = $paymentStatus['status']; // APPROVED, PENDING, CANCELLED
        if ($checkPaymentStatus == 'APPROVED') {
            $payments->update(['status' => 1]);
            RifaNumber::where('pay_id', $payments->id)->update(['status' => 1]);

            $numbersRecord = RifaNumber::where('pay_id', $payments->id)->first();
            $numbers = $numbersRecord ? json_decode($numbersRecord->numbers, true) : [];

            AwardedQuota::ganhadorBilhetePremiado(json_encode($numbers), $payments->client_id, $payments->rifas_id, $payments->id);

            $numbersBought = count($numbers);
            app(RewardEngineService::class)->grantByPurchase(
                userId: $payments->client_id,
                rifaId: $payments->rifas_id,
                numbersBought: $numbersBought,
                source: "purchase:{$payments->id}"
            );
            return response()->json([
                'status' => true,
                'message' => 'Pagamento aprovado.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Aguardando pagamento.'
        ]);
    }






    public function getCompra($id)
    {
        try {
            $buy = RifaPay::getOneCompra($id);
            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], $this->notFound);
            }
            return response()->json(["success" => true, "data" => $buy], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }
    public function getCompraClient($id)
    {
        try {
            $buy = RifaPay::getAllCompraClient($id);
            // if (!$buy) {
            //     return response()->json(["success" => false, "msg" => "Pedidos não encontrado"], $this->notFound);
            // }
            return response()->json(["success" => true, "data" => $buy], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }



    public function defineWinners(WinnerRequest $request)
    {
        try {

            $winners = RifaWinner::defineWinner($request);

            return response()->json(["success" => true, "data" => $winners], $this->success);

        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }
    public function makeQuotaAwarded(WinnerRequest $request)
    {
        try {

            $winners = RifaWinner::defineWinner($request);

            return response()->json(["success" => true, "data" => $winners], $this->success);

        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }

    public function getAllWinners()
    {
        try {
            $winners = RifaWinner::getAllWinners();

            if (!$winners) {
                return response()->json(["success" => false, "msg" => "Ganhadores não encontrado"], 404);
            }
            return response()->json(["success" => true, "data" => $winners], $this->success);

        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }














    public function update(UpdateRifasRequest $request, $id)
    {
        try {
            // $thumbnail = $request->file("thumbnail");

            $rifaData = Rifas::find($id);

            // if ($thumbnail) {

            //     $postImage =  $rifaData->thumbnail;

            //     $imagePath = parse_url($postImage, PHP_URL_PATH);

            //     if (file_exists(public_path($imagePath))) {
            //         unlink(public_path($imagePath));
            //     }

            //     $uniqueFileName = uniqid() . '.' . $thumbnail->getClientOriginalExtension();
            //     $thumbnail->move(public_path("assets/images/post-images"), $uniqueFileName);
            //     $relativePath = "assets/images/post-images/" . $uniqueFileName;

            //     $thumbnailUrl = asset($relativePath);
            // }
            $thumbnailUrl = [];
            $thumbs = $request->file("thumbnail");
            if ($thumbs) {
                for ($index = 0; $index < count($thumbs); $index += 1) {
                    $thumbnail = $thumbs[$index];
                    $uniqueFileName = uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                    $thumbnail->move(public_path("assets/images/post-images"), $uniqueFileName);
                    $relativePath = "assets/images/post-images/" . $uniqueFileName;
                    array_push($thumbnailUrl, asset($relativePath));
                }
            }
            $data = [
                "title" => strip_tags($request->get("title")),
                "description" => $request->get("description"),
                "rifa_status" => $request->get("rifaStatus"),
                "rifa_date" => $request->get("rifaDate"),
                "price" => $request->get("price"),
                "first_pacote_numbers" => $request->get("firstPacoteNumbers"),
                "first_pacote_discount" => $request->get("firstPacoteDiscount"),
                "second_pacote_numbers" => $request->get("secondPacoteNumbers"),
                "second_pacote_discount" => $request->get("secondPacoteDiscount"),
                "third_pacote_numbers" => $request->get("thirdPacoteNumbers"),
                "third_pacote_discount" => $request->get("thirdPacoteDiscount"),
                "fourth_pacote_numbers" => $request->get("fourthPacoteNumbers"),
                "fourth_pacote_discount" => $request->get("fourthPacoteDiscount"),
                "fifth_pacote_numbers" => $request->get("fifthPacoteNumbers"),
                "fifth_pacote_discount" => $request->get("fifthPacoteDiscount"),
                "sixth_pacote_numbers" => $request->get("sixthPacoteNumbers"),
                "sixth_pacote_discount" => $request->get("sixthPacoteDiscount"),
                "min_numbers" => $request->get("minNumbers"),
                "max_numbers" => $request->get("maxNumbers"),
                "rifa_numbers" => $request->get("rifaNumbers"),
            ];
            if (count($thumbnailUrl) > 0) {
                $data['thumbnail'] = implode(',', $thumbnailUrl);
            }
            $newRifaData = $rifaData->update($data);

            return response()->json(["success" => true, "data" => new RifasResource($rifaData)], $this->postSuccess);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }


    public function destroy($id)
    {
        try {
            $rifaData = Rifas::find($id);
            if ($rifaData) {
                $postImage = $rifaData->thumbnail;

                $imagePath = parse_url($postImage, PHP_URL_PATH);

                if (file_exists(public_path($imagePath))) {
                    unlink(public_path($imagePath));
                }

                $rifaData->delete();
                return response()->json(["success" => true, "msg" => "Rifa has been deleted"], $this->success);
            } else {
                throw new Exception("Rifa has not been found.");
            }
            ;
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }

    public function latest()
    {
        try {
            $rifaData = Rifas::orderBy("updated_at", "desc")->first();

            if (!$rifaData) {
                return response()->json(["success" => false, "msg" => "Rifa has not been found."], $this->notFound);
            }
            $buyer = DB::select("SELECT clients.phone, clients.name, count(rifa_numbers.client_id) numbers FROM rifa_numbers INNER JOIN clients ON rifa_numbers.client_id = clients.id INNER JOIN cotas ON cotas.id = rifa_numbers.cota_id WHERE rifa_numbers.rifa_id = $rifaData->id AND cotas.payment_status IN (1, 2, 10) AND DATE_FORMAT(cotas.created_at, '%Y-%m-%d') = CURDATE() GROUP BY client_id ORDER BY numbers DESC LIMIT 1");
            $rifaData->biggestBuyer = isset($buyer[0]) ? $buyer[0] : null;
            if (isset($rifaData->biggestBuyer->phone)) {
                $re = '*******';
                $rifaData->biggestBuyer->phone = $re . substr($rifaData->biggestBuyer->phone, 7);
            }

            return response()->json(["success" => true, "data" => new RifasResource($rifaData)], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }

    public function orders($id)
    {
        try {
            $rifaData = Rifas::find($id);

            if (!$rifaData) {
                return response()->json(["success" => false, "msg" => "Rifa has not been found."], $this->notFound);
            }
            $buyers = DB::select("SELECT cotas.payment_status, cotas.id, cotas.price, clients.phone, clients.name, count(rifa_numbers.client_id) numbers, cotas.created_at, cotas.updated_at, GROUP_CONCAT(rifa_numbers.number) nums FROM rifa_numbers INNER JOIN clients ON rifa_numbers.client_id = clients.id INNER JOIN cotas ON cotas.id = rifa_numbers.cota_id WHERE rifa_id = $rifaData->id AND cotas.payment_status IN (0, 1, 2, 10) GROUP BY client_id;");

            return response()->json(["success" => true, "data" => ["rifa" => $rifaData, "buyers" => $buyers]], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }

    public function searchOrder(Request $request)
    {
        try {
            $rifaData = Rifas::find($request->id);

            if (!$rifaData) {
                return response()->json(["success" => false, "msg" => "Rifa has not been found."], $this->notFound);
            }
            $getWhere = "";
            $conditions = [];
            if ($request->cota) {
                $cota = "rifa_numbers.number = $request->cota";
                array_push($conditions, $cota);
            }
            if ($request->buyId) {
                $buyId = "cotas.id = $request->buyId";
                array_push($conditions, $buyId);
            }
            if ($request->phone) {
                $phone = $getWhere . " clients.phone = $request->phone ";
                array_push($conditions, $phone);
            }
            if (count($conditions) > 0) {
                $con = implode(" OR ", $conditions);
                $getWhere = "AND ($con)";
            }
            $buyers = DB::select("SELECT cotas.payment_status, cotas.id, cotas.price, clients.phone, clients.name, count(rifa_numbers.client_id) numbers, cotas.created_at, cotas.updated_at, GROUP_CONCAT(rifa_numbers.number) nums FROM rifa_numbers INNER JOIN clients ON rifa_numbers.client_id = clients.id INNER JOIN cotas ON cotas.id = rifa_numbers.cota_id WHERE rifa_id = $rifaData->id AND cotas.payment_status IN (0, 1, 2, 10) $getWhere GROUP BY client_id LIMIT 1");
            $buyer = isset($buyers[0]) ? $buyers[0] : null;

            return response()->json(["success" => true, "data" => ["buyer" => $buyer]], $this->success);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], $this->serverError);
        }
    }


    public function defineWinner(Request $request)
    {
        try {
            $rifaData = Rifas::find($request->id);

            if (!$rifaData) {
                return response()->json(["success" => false, "message" => "Rifa não encontrada."], 404);
            }

            $buyers = DB::select("SELECT cotas.payment_status, cotas.id, cotas.price, clients.id client_id, clients.phone, clients.name, count(rifa_numbers.client_id) numbers, cotas.created_at, cotas.updated_at, GROUP_CONCAT(rifa_numbers.number) nums FROM rifa_numbers INNER JOIN clients ON rifa_numbers.client_id = clients.id INNER JOIN cotas ON cotas.id = rifa_numbers.cota_id WHERE rifa_id = $rifaData->id AND cotas.payment_status IN (1, 2, 10) AND rifa_numbers.number = $request->cotaId GROUP BY client_id LIMIT 1");
            if (isset($buyers[0])) {
                $rifaData->update(['winner_id' => $buyers[0]->client_id, 'winner_number' => $request->cotaId]);
                return response()->json(["success" => true, "data" => ["buyer" => $buyers[0]]], $this->success);
            } else {
                return response()->json(["success" => false, "message" => "Cota não encontrada"], 404);
            }
        } catch (Exception $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], $this->serverError);
        }
    }

    public function getNumerosPremiados($id)
    {
        try {
            $rifaPremiadas = AwardedQuota::getAllBilhetesPremiadosWithGet($id);
            return response()->json(["success" => true, "data" => $rifaPremiadas], $this->success);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }
}
