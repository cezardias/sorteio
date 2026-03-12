<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Afiliado;
use App\Models\PaymentInfo;
use App\Models\SiteSetting;
use App\Models\V1\{Clients, Rifas, RifaWinner, RifaPay, AwardedQuota};
use App\Models\RewardTypes;
use App\Models\RewardItems;
use Illuminate\Support\Facades\DB;
use App\Models\V1\RifaNumber;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use ApiGratis\ApiBrasil;
use App\Models\User;
use App\Http\Requests\V1\{UserRequest};
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use App\Services\RifaService;


class AdminController extends Controller
{

    protected $rifaService;

    public function __construct(RifaService $rifaService)
    {
        $this->rifaService = $rifaService;
    }
    public function storeUser(UserRequest $request) {
        try {
            $user = User::createUser($request->validated());
            return response()->json(['response' => 'Usuário criado com sucesso', 'user' => $user], 201);
        } catch (\Throwable $e) {
            return response()->json(['response' => 'Ocorreu um erro interno', 'error' => $e->getMessage()], 500);
        }
    }
    public function destroyUser($id) {
        $this->authorize('create', User::class);
        try {
            $user = User::find($id);
            if (!$user ) {
                return response()->json(["success" => false, "msg" =>"Usuario não encontrado"], 404);
            }
            $user->delete();
            return response()->json(['response' => 'Usuário deletado com sucesso'], 201);
        } catch (\Throwable $e) {
            return response()->json(['response' => 'Ocorreu um erro interno', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request) {
        try {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json(['response' => 'Login efetuado com sucesso', 'token' => $token, 'user' => $user], 200);
            }

            return response()->json(['response' => 'Login inválido'], 401);
        } catch (\Throwable $e) {
            return response()->json(['response' => 'Ocorreu um erro interno', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request) {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['response' => 'Logout efetuado com sucesso'], 200);
        } catch (\Throwable $e) {
            return response()->json(['response' => 'Ocorreu um erro interno', 'error' => $e->getMessage()], 500);
        }
    }

    public function procurarGanhadorPeloNumero(Request $request) {
        $this->authorize('view', User::class);
        try {
            $ganhador = $this->rifaService->procurarGanhador($request->numeroWinner, $request->rifa_id);

            if (!$ganhador) {
                return response()->json(["success" => false, "msg" => 'Ganhador não encontrado'], 404);
            }

            return response()->json(["success" => true, "data" => $ganhador], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function procurarClientCellphone(Request $request) {
        $this->authorize('view', User::class);
        try {

            $client = Clients::findClient($request->cellphone);

            if (!$client) {
                return response()->json(["success" => false, "msg" => 'Usuário não encontrado'], 404);
            }

            return response()->json(["success" => true, "data" => $client], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function definirGanhador(Request $request) {
         $this->authorize('create', User::class);
        try {
            $ganhador = $this->rifaService->definirGanhador($request->numeroSorteado, $request->novoGanhadorPhone, $request->rifa_id);

            return response()->json(["success" => true, "data" => $ganhador], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function cadastrarGanhador(Request $request) {
        $this->authorize('create', User::class);
        try {
            $image = $this->rifaService->saveImage($request->img, $request->rifas_id);
            $client = Clients::findClient( $request->cellphone);
            if (!$client ) {
                return response()->json(["success" => false, "msg" =>"Cliente não encontrado"], 404);
            }
            $winnerId = RifaWinner::defineWinner($request, $client->id, $image['imgName']);
            $winner = RifaWinner::findWinner($winnerId->id);

            return response()->json(["success" => true, "data" => $winner], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function getOneGanhador($id) {
        $this->authorize('view', User::class);
        try {
            $winner = RifaWinner::findWinner($id);
            if (!$winner ) {
                return response()->json(["success" => false, "msg" =>"Vencedor não encontrado"], 404);
            }

            return response()->json(["success" => true, "data" => $winner], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function editarGanhador(Request $request) {
        $this->authorize('update', User::class);
        try {
            $winner = RifaWinner::findWinner($request->id);
            if (!$winner ) {
                return response()->json(["success" => false, "msg" =>"Vencedor não encontrado"], 404);
            }

            // $tel = $request->cellphone ?? $request->client->cellphone;
            $client = Clients::findClient($request->cellphone);
            if (!$client ) {
                return response()->json(["success" => false, "msg" =>"Cliente não encontrado"], 404);
            }

            $isImg = preg_match('#^data:image/(?<type>.+);base64,#', $request->img);
            if($isImg) {
                $image = $this->rifaService->saveImage($request->img, $request->rifas_id);
                RifaWinner::editarWinner($request, $client->id, $image['imgName']);
            } else {
                RifaWinner::editarWinner($request, $client->id, $request->img);
            }


            $winners = RifaWinner::getAllWinners();
            return response()->json(["success" => true, "data" => $winners], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function destroyGanhador($id) {
        $this->authorize('delete', User::class);
        try {
            $winner = RifaWinner::findWinner($id);
            if (!$winner ) {
                return response()->json(["success" => false, "msg" =>"Vencedor não encontrado"], 404);
            }
            $winner->delete();
            $winners = RifaWinner::getAllWinners();
            return response()->json(["success" => true, "msg" => "Ganhador excluído com sucesso!", "data" =>$winners], 201);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function adicionarNumerosRifas(Request $request) {
        $this->authorize('create', User::class);
        try {

            if($request->tipo == "aleatorio") {
                $client = $this->rifaService->adicionarNumerosRifasClient($request->cellphone, $request->qntd_number, $request->rifa_id);

                if(!$client['success']) {
                    return response()->json(["success" => true, "msg" => $client['msg']], 404);
                }
            } else if($request->tipo == "definir") {
                $client = $this->rifaService->adicionarNumerosDefinidoRifasClient($request->cellphone, $request->qntd_number, $request->rifa_id);

                if(!$client['success']) {
                    return response()->json(["success" => true, "msg" => $client['msg']], 404);
                }
            } else {
                return response()->json(["success" => true, "msg" => 'Opção invalida'], 404);
            }
            return response()->json(["success" => true, "msg" => "Número Adicionado com sucesso"], 201);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function adicionarBilhetePremiado(Request $request) {
        $this->authorize('create', User::class);
        try {
            $client = $this->rifaService->addBilhetePremiado($request->cellphone, $request->numero_premiado, $request->rifa_id);

            if(!$client['success']) {
                return response()->json(["success" => true, "msg" => $client['msg']], 404);
            }

            return response()->json(["success" => true, "msg" => "Bilhete premiado adicionado com sucesso"], 201);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function rifaAtivas() {
        try {
            $rifas = Rifas::getAllRifasActivas();

            if(!$rifas) {
                return response()->json(["success" => true, "msg" => 'rifas não encontradas'], 404);
            }

            return response()->json(["success" => true, "data" => $rifas], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function consultaCota($id) {
        try {
            $rifas = Rifas::getOneRifa($id);

            if(!$rifas) {
                return response()->json(["success" => true, "msg" => 'rifas não encontradas'], 404);
            }

            return response()->json(["success" => true, "data" => $rifas], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

   public function consultaCotaMinAndMax(Request $request) {
    try {
        $rifaId = intval($request->rifa_id);
        $data = $request->date;
        $type = $request->type;
        $search = $request->search;

        // Formata a data corretamente
        $dataFormatada = $data ? Carbon::parse($data)->toDateString() : null;

        // Filtra os números da rifa na data especificada (se houver)
        $query = RifaNumber::where('rifas_id', $rifaId)
            ->where('status', 1);

        if ($dataFormatada) {
            $query->whereDate('created_at', $dataFormatada);
        }

        $query = $query->get();

        // Mapeia os números e os transforma em strings com zeros à esquerda
        $allNumbers = $query->flatMap(function($item) {
            return collect(json_decode($item->numbers, true))
                ->map(function($number) {
                    return str_pad((string) $number, 5, '0', STR_PAD_LEFT);
                });
        })->toArray();

        $result = null;
        $searchResult = null;

        if ($type === 'min') {
            $minNumber = !empty($allNumbers) ? min($allNumbers) : null;
            if ($minNumber !== null) {
                $resultQuery = RifaNumber::where('rifas_id', $rifaId)
                    ->whereJsonContains('numbers', $minNumber)
                    ->with(['rifa', 'client']);

                if ($dataFormatada) {
                    $resultQuery->whereDate('created_at', $dataFormatada);
                }

                $result = $resultQuery->first();
            }
            $searchResult = $minNumber;
        } elseif ($type === 'max') {
            $maxNumber = !empty($allNumbers) ? max($allNumbers) : null;
            if ($maxNumber !== null) {
                $result = RifaNumber::where('rifas_id', $rifaId)
                    ->whereJsonContains('numbers', $maxNumber)
                    ->with(['rifa', 'client'])
                    ->first();
            }
            $searchResult = $maxNumber;
        } else {
            $result = $this->rifaService->procurarGanhador($search, $rifaId);
            return response()->json(['data' => $result, 'search' => $search], 200);
        }

        return response()->json(['data' => $result, 'search' => $searchResult], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Ocorreu um erro ao processar a solicitação.',
            'message' => $e->getMessage()
        ], 500);
    }
}













    public function getPedidos() {
        try {
            $buy = RifaPay::getAllCompra();
            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> $buy], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function getPedidosFiltro(Request $request) {
        try {
            $filters = $request->all();
            $buy = RifaPay::getPedidosFiltro($filters);

            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }

            return response()->json(["success" => true, "data" => $buy], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }


    public function filtroRifas(Request $request, $rifasId)
    {
        try {
            $query = RifaPay::query()->where('rifas_id', $rifasId)->with(['rifa', 'client']);
            $rifa = Rifas::findRifa($rifasId);
            // Aplicar filtros baseados na solicitação
            if ($request->has('startDate') && $request->input('startDate')) {
                $query->where('created_at', '>=', $request->input('startDate'));
            }
            if ($request->has('endDate') && $request->input('endDate')) {
                $query->where('created_at', '<=', $request->input('endDate'));
            }
            if ($request->has('qtdCotas') && is_numeric($request->input('qtdCotas'))) {
                $query->where('qntd_number', '>=', (int) $request->input('qtdCotas'));
            }

            // Verificar o tipo e aplicar limite se necessário
            $tipo = $request->input('tipo');
            if ($tipo === 'TC') {
                // Agrupar e somar por client_id
                $result = $query->select('rifas_id', 'created_at', 'client_id', DB::raw('sum(qntd_number) as qntd_number'))
                    ->groupBy('client_id')
                    ->get();
            } else {
                // Aplicar limite se a quantidade de sortear estiver presente
                if ($request->has('qtdSortear') && is_numeric($request->input('qtdSortear'))) {
                    $query->limit((int) $request->input('qtdSortear'));
                }
                $result = $query->get();
            }
            // Retornar a resposta com os dados filtrados
            return response()->json(['data' => $result, 'rifa' => $rifa]);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }









    public function getOnePedidos($idRifa, $idClient) {
        try {
            $buy = RifaPay::getOneCompraClientByRifa($idRifa, $idClient);
            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> $buy], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function cancelarPedidos($id) {
        try {
            $buy = RifaPay::cancelarCompra($id);
            RifaNumber::cancelarCompra($id );
            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> "Compra cancelada"], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function aprovarPedidos($id) {
        try {
            $buy = RifaPay::aprovarCompra($id);
            RifaNumber::aprovarCompra($id);
            $numbers = RifaNumber::where('pay_id', $id)->pluck('numbers')->toArray();
            $rifaPay = RifaPay::find($id);
            AwardedQuota::ganhadorBilhetePremiado($numbers, $rifaPay->client_id, $rifaPay->rifas_id, $rifaPay->id);
            if (!$buy) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> "Compra cancelada"], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function allClients() {
        try {
            $clientes = Clients::getAllClient();
            if (!$clientes) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> $clientes], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function allClientsFiltro(Request $request) {
        try {
            $query = Clients::query();

            if ($request->has('id')) {
                $query->where('id', $request->input('id'));
            }

            if ($request->has('name')) {
                $name = $request->input('name');
                $nameParts = explode(' ', $name);

                $query->where(function ($q) use ($nameParts) {
                    foreach ($nameParts as $part) {
                        $q->where(function ($subQ) use ($part) {
                            $subQ->where('name', 'like', '%' . $part . '%')
                                 ->orWhere('surname', 'like', '%' . $part . '%');
                        });
                    }
                });
            }


            if ($request->has('cellphone')) {
                $query->where('cellphone', 'like', '%' . $request->input('cellphone') . '%');
            }

            $clientes = $query->get();

            if ($clientes->isEmpty()) {
                return response()->json(["success" => false, "msg" => "Clientes não encontrados"], 404);
            }

            return response()->json(["success" => true, "data" => $clientes], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }


    public function getOneClient($id) {
        try {
            $clientes = Clients::findClientById($id);
            if (!$clientes) {
                return response()->json(["success" => false, "msg" => "Pedido não encontrado"], 404);
            }
            return response()->json(["success" => true, "data"=> $clientes], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function editarClients(Request $request) {
        try {
            $cliente = Clients::editarClient($request);
            if (!$cliente) {
                return response()->json(["success" => false, "msg" => "Cliente não atualizado"], 404);
            }
            return response()->json(["success" => true, "data"=> $cliente], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function rankingGeral() {
        try {
            $ranking = RifaNumber::getRankingRifaGeral();
            if (!$ranking) {
                return response()->json(["success" => false, "msg" => "Ranking não atualizado"], 404);
            }

            return response()->json(["success" => true, "data"=> $ranking], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function rankingGeralFiltro(Request $request) {
        try {
            $ranking = RifaNumber::getRankingRifaGeralFiltro(
                $request->input('total_numbers'),
                $request->input('rifas_id'),
                $request->input('startDate'),
                $request->input('endDate')
            );

            if (!$ranking) {
                return response()->json(["success" => false, "msg" => "Ranking não atualizado"], 404);
            }

            return response()->json(["success" => true, "data" => $ranking], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }


    public function getAllUsers() {
        try {
            $users = User::allUsers();
            if (!$users) {
                return response()->json(["success" => false, "msg" => "Usarios não encontrados"], 404);
            }

            return response()->json(["success" => true, "data"=> $users], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function getAllUsersFiltro(Request $request) {
        try {
            $input = $request->input('query');
            $users = User::allUsersFiltro($input);

            if (!$users) {
                return response()->json(["success" => false, "msg" => "Usuários não encontrados"], 404);
            }

            return response()->json(["success" => true, "data" => $users], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function getOneUser($id) {
        try {
            $user = User::getOneUser($id);
            if (!$user) {
                return response()->json(["success" => false, "msg" => "Usarios não encontrados"], 404);
            }

            return response()->json(["success" => true, "data"=> $user], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function editarUsers(Request $request) {
        try {
            $validatedData = $request->validate([
                'id' => 'required|integer|exists:users,id',
                'name' => 'sometimes|string|max:191',
                'cpf' => 'sometimes|nullable|max:191',
                'password' => 'sometimes|nullable|max:191',
                'cellphone' => 'sometimes|nullable|max:191',
                'role' => 'sometimes|nullable|string|max:191',
                'email' => 'sometimes|email|max:191',
            ]);

            $user = User::getOneUser($request->id);
            if (!$user) {
                return response()->json(["success" => false, "msg" => "Usuário não encontrado"], 404);
            }

            $user->update($validatedData);

            return response()->json(["success" => true, "data"=> "Usuário editado com sucesso"], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function getAllGateway() {
        try {
            $paymentInfos = PaymentInfo::all();
            return response()->json(["success" => true, "data" => $paymentInfos], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function showGateway($id) {
        try {
            $paymentInfo = PaymentInfo::findOrFail($id);
            return response()->json(["success" => true, "data" => $paymentInfo], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function storeGateway(Request $request) {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|max:255',
                'gateway' => 'sometimes|required|max:255',
                'api_client_id' => 'sometimes|required|max:255',
                'public_key' => 'sometimes|required|max:255',
                'billing_name' => 'sometimes|nullable|max:255',
            ]);

            $paymentInfo = PaymentInfo::create($data);

            return response()->json(["success" => true, "data" => $paymentInfo], 201);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }
    public function updateGateway(Request $request) {
        try {
            $paymentInfo = PaymentInfo::findOrFail($request->id);

            $data = $request->validate([
                'name' => 'sometimes|required|max:255',
                'gateway' => 'sometimes|required|max:255',
                'api_client_id' => 'sometimes|required|max:255',
                'public_key' => 'sometimes|required|max:255',
                'billing_name' => 'sometimes|nullable|max:255',
            ]);

            $paymentInfo->update($data);

            return response()->json(["success" => true, "data" => $paymentInfo], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }
    public function destroyGateway($id) {
        try {
            $paymentInfo = PaymentInfo::findOrFail($id);
            $paymentInfo->delete();

            return response()->json(["success" => true, "msg" => "Registro deletado com sucesso"], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }

    public function getConfigSite() {
        try {
            $settings = SiteSetting::first();
            return response()->json(["success" => true, "data" => $settings], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }

    public function storeConfigSite(Request $request)
    {
        try {
            $data = $request->all();

            if (isset($request->id)) {
                $config = SiteSetting::find($request->id);

                if (!$config) {
                    return response()->json(["success" => false, "msg" => "Configuração não encontrada"], 404);
                }

                $config->update($data);
                $setting = $config;
            } else {
                $setting = SiteSetting::create($data);
            }

            return response()->json(["success" => true, "data" => $setting], 201);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function getVendas() {
        try {
            // Total de pedidos e valores
            $totalPedido = RifaPay::getAllCompraAtivo()->sum('value');
            $pedidosAprovados = RifaPay::getAllCompraAtivo()->count('status');
            $pedidosAguardando = RifaPay::getAllCompraAguardando()->count('status');
            $totalPedidoAguardando = RifaPay::getAllCompraAguardando()->sum('value');

            // Faturamento por hora do dia
            $horaDoDia = $this->faturamentoPorHoraAll();

            // Faturamento Semanal
            $inicioDaSemana = Carbon::now()->startOfWeek(Carbon::SUNDAY);
            $fimDaSemana = Carbon::now()->endOfWeek(Carbon::SATURDAY);
            $faturamentoSemanal = [];

            // Iterando por cada dia da semana
            $diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
            foreach ($diasSemana as $indice => $dia) {
                $diaAtual = $inicioDaSemana->copy()->addDays($indice)->startOfDay();
                $fimDiaAtual = $diaAtual->copy()->endOfDay();

                $faturamentoDia = RifaPay::getAllCompraAtivo()
                    ->whereBetween('created_at', [$diaAtual, $fimDiaAtual])
                    ->sum('value');

                $faturamentoSemanal[$dia] = $faturamentoDia;
            }

            // Faturamento do Dia Atual
            $inicioDoDia = Carbon::now()->startOfDay();
            $faturamentoDoDia = RifaPay::getAllCompraAtivo()
                ->where('created_at', '>=', $inicioDoDia)
                ->sum('value');

            // Faturamento Diário
            $faturamentoDiario = $this->faturamentoDiarioAll();

            // Faturamento Acumulado
            $faturamentoAcumulado = $this->faturamentoAcumuladoAll();

            // Faturamento Semanal por Dia da Semana
            $faturamentoSemanalPorDiaDaSemana = $this->faturamentoSemanalPorDiaAll();

            return response()->json([
                "success" => true,
                "data" => [
                    'totalPedido' => $totalPedido,
                    'pedidosAprovados' => $pedidosAprovados,
                    'pedidosAguardando' => $pedidosAguardando,
                    'totalPedidoAguardando' => $totalPedidoAguardando,
                    'horaDoDia' => $horaDoDia,
                    'faturamentoSemanal' => $faturamentoSemanal,
                    'faturamentoDoDia' => $faturamentoDoDia,
                    'faturamentoDiario' => $faturamentoDiario,
                    'faturamentoAcumulado' => $faturamentoAcumulado,
                    'faturamentoSemanalPorDiaDaSemana' => $faturamentoSemanalPorDiaDaSemana
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    private function faturamentoPorHoraAll() {

         // Inicializa um array de 24 horas com valor 0
         $resultados = array_fill(0, 24, 0);

         // Obtém as compras ativas
         $compraAtivo = RifaPay::getAllCompraAtivo();

         foreach ($compraAtivo as $compra) {
             // Extrai a hora do timestamp da compra
             $hora = Carbon::parse($compra->created_at)->format('H');

             // Converte a hora para inteiro
             $horaInt = (int)$hora;

             // Adiciona o valor ao índice correspondente
             if (isset($resultados[$horaInt])) {
                 $resultados[$horaInt] += $compra->value;
             }
         }

         return $resultados;

    }

    // Faturamento Diário
    private function faturamentoDiarioAll() {
        $hoje = Carbon::now();
        $inicioDoDia = $hoje->copy()->subDays(5)->startOfDay(); // Data de 5 dias atrás
        $fimDoDia = $hoje->endOfDay(); // Data do final do dia de hoje

        $resultados = [];

        while ($inicioDoDia->lte($fimDoDia)) {
            // Calcula o total de pedidos e o total aprovado para o dia
            $pedidosDia = RifaPay::getAllCompraAtivo()
                ->whereBetween('created_at', [$inicioDoDia->copy()->startOfDay(), $inicioDoDia->copy()->endOfDay()]);

            $totalPedidosDia = $pedidosDia->count(); // Número total de pedidos
            $totalAprovadoDia = $pedidosDia->sum('value'); // Valor total aprovado

            // Adiciona o resultado ao array usando o formato brasileiro para a chave
            $resultados[$inicioDoDia->format('d/m/Y')] = [
                'totalPedidos' => $totalPedidosDia,
                'totalAprovado' => $totalAprovadoDia,
            ];

            // Avança para o próximo dia
            $inicioDoDia->addDay();
        }

        return $resultados;
    }

    public function faturamentoAcumuladoAll() {
        $resultados = [];
        $datas = $this->obterDatasAll(); // Obtém as datas de hoje e dos últimos 3 dias

        foreach ($datas as $data) {
            $inicioDoDia = Carbon::parse($data)->startOfDay();
            $fimDoDia = Carbon::parse($data)->endOfDay();
            $pedidosDia = RifaPay::getAllCompraAtivo()
                ->whereBetween('created_at', [$inicioDoDia, $fimDoDia]);
            $totalPedidosDia = $pedidosDia->count();
            $totalAprovadoDia = $pedidosDia->sum('value');
            $resultados[$data] = [
                'totalPedidos' => $totalPedidosDia,
                'totalAprovado' => $totalAprovadoDia,
            ];
        }

        return $resultados;
    }


    public function obterDatasAll() {
        $datas = [];
        $hoje = Carbon::now();
        $datas[] = $hoje->format('Y-m-d'); // Data de hoje

        for ($i = 1; $i <= 3; $i++) {
            $datas[] = $hoje->copy()->subDays($i)->format('Y-m-d');
        }

        return $datas;
    }

    // Faturamento Semanal por Dia da Semana
    private function faturamentoSemanalPorDiaAll() {
        $resultados = [
            'Monday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Tuesday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Wednesday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Thursday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Friday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Saturday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Sunday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
        ];

        $inicioDaSemana = Carbon::now()->startOfWeek();
        $fimDaSemana = Carbon::now()->endOfWeek();

        foreach ($resultados as $diaDaSemana => &$valores) {
            $diaCarbon = Carbon::parse("next $diaDaSemana");

            if ($diaCarbon->lessThan($inicioDaSemana)) {
                $diaCarbon->addWeek();
            }

            if ($diaCarbon->greaterThan($fimDaSemana)) {
                continue;
            }

            $inicioDoDia = $diaCarbon->startOfDay();
            $fimDoDia = $diaCarbon->endOfDay();
            $pedidosDia = RifaPay::getAllCompraAtivo()
                ->whereBetween('created_at', [$inicioDoDia, $fimDoDia]);
            $valores['totalPedidos'] = $pedidosDia->count();
            $valores['totalAprovado'] = $pedidosDia->sum('value');
        }

        return $resultados;
    }


    public function vendasFiltro(Request $request) {
        try {
            // Parâmetros de data inicial e final
            $dataInicio = $request->input('startDate');
            $dataFim = $request->input('endDate');

            // Verificar se os parâmetros são datas válidas
            if (!strtotime($dataInicio) || !strtotime($dataFim)) {
                return response()->json(["success" => false, "msg" => "Parâmetros de data inválidos"], 400);
            }

            // Converter as datas para objetos Carbon
            $inicio = Carbon::parse($dataInicio)->startOfDay();
            $fim = Carbon::parse($dataFim)->endOfDay();

            // Filtrar pedidos entre as datas fornecidas
            $comprasFiltradas = RifaPay::getAllCompraAtivo()
                ->whereBetween('created_at', [$inicio, $fim]);

            $totalPedido = $comprasFiltradas->sum('value');
            $pedidosAprovados = $comprasFiltradas->count();
            $pedidosAguardando = RifaPay::getAllCompraAguardando()
                ->whereBetween('created_at', [$inicio, $fim])
                ->count();
            $totalPedidoAguardando = RifaPay::getAllCompraAguardando()
                ->whereBetween('created_at', [$inicio, $fim])
                ->sum('value');

            $horaDoDia = [];
            foreach ($comprasFiltradas as $compra) {
                $hora = Carbon::parse($compra->created_at)->format('H'); // Hora do dia (0-23)
                if (!isset($horaDoDia[$hora])) {
                    $horaDoDia[$hora] = 0;
                }
                $horaDoDia[$hora] += $compra->value;
            }

            // Faturamento Semanal (considerando a semana da data inicial)
            $inicioDaSemana = $inicio->copy()->startOfWeek();
            $fimDaSemana = $inicio->copy()->endOfWeek();
            $faturamentoSemanal = RifaPay::getAllCompraAtivo()
                ->whereBetween('created_at', [$inicioDaSemana, $fimDaSemana])
                ->sum('value');

            // Faturamento do Dia Atual (considerando o dia inicial)
            $inicioDoDia = $inicio->copy()->startOfDay();
            $faturamentoDoDia = RifaPay::getAllCompraAtivo()
                ->where('created_at', '>=', $inicioDoDia)
                ->sum('value');

            return response()->json([
                "success" => true,
                "data" => [
                    'totalPedido' => $totalPedido,
                    'pedidosAprovados' => $pedidosAprovados,
                    'pedidosAguardando' => $pedidosAguardando,
                    'totalPedidoAguardando' => $totalPedidoAguardando,
                    'horaDoDia' => $horaDoDia,
                    'faturamentoSemanal' => $faturamentoSemanal,
                    'faturamentoDoDia' => $faturamentoDoDia
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function vendasFiltroOne(Request $request, $id) {
        try {
            // Parâmetros de data inicial e final
            $dataInicio = $request->input('startDate');
            $dataFim = $request->input('endDate');

            // Verificar se os parâmetros são datas válidas
            if (!strtotime($dataInicio) || !strtotime($dataFim)) {
                return response()->json(["success" => false, "msg" => "Parâmetros de data inválidos"], 400);
            }

            // Converter as datas para objetos Carbon
            $inicio = Carbon::parse($dataInicio)->startOfDay();
            $fim = Carbon::parse($dataFim)->endOfDay();

            // Filtrar pedidos entre as datas fornecidas
            $comprasFiltradas = RifaPay::getOneCompraAtivo($id)
                ->whereBetween('created_at', [$inicio, $fim]);

            $totalPedido = $comprasFiltradas->sum('value');
            $pedidosAprovados = $comprasFiltradas->count();
            $pedidosAguardando = RifaPay::getOneCompraAguardando($id)
                ->whereBetween('created_at', [$inicio, $fim])
                ->count();
            $totalPedidoAguardando = RifaPay::getOneCompraAguardando($id)
                ->whereBetween('created_at', [$inicio, $fim])
                ->sum('value');

            // Faturamento Horário
            $horaDoDia = $this->faturamentoPorHora($id, $inicio, $fim);

            // Faturamento Semanal
            $inicioDaSemana = $inicio->copy()->startOfWeek();
            $fimDaSemana = $inicio->copy()->endOfWeek();
            $faturamentoSemanal = $this->faturamentoSemanalPorDia($id, $inicioDaSemana, $fimDaSemana);

            // Faturamento do Dia Atual
            $faturamentoDoDia = RifaPay::getOneCompraAtivo($id)
                ->whereBetween('created_at', [$inicio, $fim])
                ->sum('value');

            // Faturamento Diário
            $diario = $this->faturamentoDiario($id, $inicio->month, $inicio->year);

            // Faturamento Acumulado
            $acumulado = $this->faturamentoAcumulado($id, $inicio->year);

            return response()->json([
                "success" => true,
                "data" => [
                    'totalPedido' => $totalPedido,
                    'pedidosAprovados' => $pedidosAprovados,
                    'pedidosAguardando' => $pedidosAguardando,
                    'totalPedidoAguardando' => $totalPedidoAguardando,
                    'horaDoDia' => $horaDoDia,
                    'faturamentoSemanal' => $faturamentoSemanal,
                    'faturamentoDoDia' => $faturamentoDoDia,
                    'faturamentoDiario' => $diario,
                    'faturamentoAcumulado' => $acumulado
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }



    public function getOneVendas($id) {
        try {
            $nomeRifa = Rifas::where('id', $id)->first();

            // Lógica existente
            $totalPedido = RifaPay::getOneCompraAtivo($id)->sum('value');
            $pedidosAprovados = RifaPay::getOneCompraAtivo($id)->count('status');
            $pedidosAguardando = RifaPay::getOneCompraAguardando($id)->count('status');
            $totalPedidoAguardando = RifaPay::getOneCompraAguardando($id)->sum('value');

            // Faturamento Horário
            $horaDoDia = $this->faturamentoPorHora($id);

            // Faturamento Semanal (de Domingo a Sábado)
            $inicioDaSemana = Carbon::now()->startOfWeek(Carbon::SUNDAY);
            $fimDaSemana = Carbon::now()->endOfWeek(Carbon::SATURDAY);
            $faturamentoSemanal = [];

            // Iterando por cada dia da semana
            $diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
            foreach ($diasSemana as $indice => $dia) {
                $diaAtual = $inicioDaSemana->copy()->addDays($indice)->startOfDay();
                $fimDiaAtual = $diaAtual->copy()->endOfDay();

                $faturamentoDia = RifaPay::getOneCompraAtivo($id)
                    ->whereBetween('created_at', [$diaAtual, $fimDiaAtual])
                    ->sum('value');

                $faturamentoSemanal[$dia] = $faturamentoDia;
            }

            // Faturamento do Dia Atual
            $inicioDoDia = Carbon::now()->startOfDay();
            $faturamentoDoDia = RifaPay::getOneCompraAtivo($id)
                ->where('created_at', '>=', $inicioDoDia)
                ->sum('value');

            // Faturamento Diário (últimos 5 dias)
            $mesAtual = Carbon::now()->month;
            $anoAtual = Carbon::now()->year;
            $diario = $this->faturamentoDiario($id, $mesAtual, $anoAtual);

            // Faturamento Acumulado
            $acumulado = $this->faturamentoAcumulado($id, $anoAtual);

            // Faturamento Semanal por Dia da Semana
            $semanalPorDia = $this->faturamentoSemanalPorDia($id);

            $afiliados = Afiliado::with(['client', 'ganhoAfiliado' => function($query) use ($id) {
                $query->where('rifas_id', $id);
            }])->get();

            if ($afiliados->isEmpty()) {
                return response()->json(["success" => false, "msg" => 'Afiliado não existe'], 500);
            }
            foreach ($afiliados as $afiliado) {
                $afiliado->totalPedidos = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->count() :  0;
                $afiliado->faturamento = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('faturamento') :  0;
                $afiliado->comissao = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('comissao') :  0;
            }

            return response()->json([
                "success" => true,
                "data" => [
                    'totalPedido' => $totalPedido,
                    'pedidosAprovados' => $pedidosAprovados,
                    'pedidosAguardando' => $pedidosAguardando,
                    'totalPedidoAguardando' => $totalPedidoAguardando,
                    'horaDoDia' => $horaDoDia,
                    'faturamentoSemanal' => $faturamentoSemanal,
                    'faturamentoDoDia' => $faturamentoDoDia,
                    'faturamentoDiario' => $diario,
                    'faturamentoAcumulado' => $acumulado,
                    'semanalPorDia' => $semanalPorDia,
                    'nomeRifa' => $nomeRifa,
                    'afiliados' => $afiliados,
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    private function faturamentoDiario($id, $mes, $ano) {
        $hoje = Carbon::now();
        $inicioDoDia = $hoje->copy()->subDays(5)->startOfDay(); // Data de 5 dias atrás
        $fimDoDia = $hoje->endOfDay(); // Data do final do dia de hoje

        $resultados = [];

        while ($inicioDoDia->lte($fimDoDia)) {
            // Calcula o total de pedidos e o total aprovado para o dia
            $pedidosDia = RifaPay::getOneCompraAtivo($id)
                ->whereBetween('created_at', [$inicioDoDia->copy()->startOfDay(), $inicioDoDia->copy()->endOfDay()]);

            $totalPedidosDia = $pedidosDia->count(); // Número total de pedidos
            $totalAprovadoDia = $pedidosDia->sum('value'); // Valor total aprovado

            // Adiciona o resultado ao array usando o formato brasileiro para a chave
            $resultados[$inicioDoDia->format('d/m/Y')] = [
                'totalPedidos' => $totalPedidosDia,
                'totalAprovado' => $totalAprovadoDia,
            ];

            // Avança para o próximo dia
            $inicioDoDia->addDay();
        }

        return $resultados;
    }

    private function faturamentoAcumulado($id, $ano) {
        $resultados = [];
        $datas = $this->obterDatas();

        foreach ($datas as $data) {
            $inicioDoDia = Carbon::createFromFormat('Y-m-d', $data)->startOfDay();
            $fimDoDia = Carbon::createFromFormat('Y-m-d', $data)->endOfDay();

            // Calcula o total de pedidos e o total aprovado para o dia
            $pedidosDia = RifaPay::getOneCompraAtivo($id)
                ->whereBetween('created_at', [$inicioDoDia, $fimDoDia]);

            $totalPedidosDia = $pedidosDia->count(); // Número total de pedidos
            $totalAprovadoDia = $pedidosDia->sum('value'); // Valor total aprovado

            $resultados[$data] = [
                'totalPedidos' => $totalPedidosDia,
                'totalAprovado' => $totalAprovadoDia,
            ];
        }

        return $resultados;
    }
    public function obterDatas() {
    $datas = [];
    $hoje = Carbon::now();
    $datas[] = $hoje->format('Y-m-d'); // Data de hoje

    for ($i = 1; $i <= 3; $i++) {
        $datas[] = $hoje->copy()->subDays($i)->format('Y-m-d');
    }

    return $datas;
}
    private function faturamentoSemanalPorDia($id)
    {
        // Definindo os dias da semana
        $resultados = [
            'Monday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Tuesday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Wednesday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Thursday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Friday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Saturday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
            'Sunday' => ['totalPedidos' => 0, 'totalAprovado' => 0],
        ];

        // Obtém o início da semana e o fim da semana
        $inicioDaSemana = Carbon::now()->startOfWeek();
        $fimDaSemana = Carbon::now()->endOfWeek();

        \Log::info('Início da Semana', ['inicioDaSemana' => $inicioDaSemana]);
        \Log::info('Fim da Semana', ['fimDaSemana' => $fimDaSemana]);

        // Itera sobre cada dia da semana
        foreach ($resultados as $diaDaSemana => &$valores) {
            // Obtém o próximo dia da semana
            $diaCarbon = Carbon::now()->startOfWeek()->copy()->addDays(array_search($diaDaSemana, array_keys($resultados)));

            // Se o dia calculado for na semana anterior, adicione uma semana
            if ($diaCarbon->lessThan($inicioDaSemana)) {
                $diaCarbon->addWeek();
            }

            // Verifica se o dia calculado está dentro do intervalo da semana atual
            if ($diaCarbon->greaterThan($fimDaSemana)) {
                continue;
            }

            $inicioDoDia = $diaCarbon->startOfDay();
            $fimDoDia = $diaCarbon->endOfDay();

            \Log::info('Dia da Semana', [
                'diaDaSemana' => $diaDaSemana,
                'diaCarbon' => $diaCarbon->toDateString(),
                'inicioDoDia' => $inicioDoDia->toDateTimeString(),
                'fimDoDia' => $fimDoDia->toDateTimeString()
            ]);

            // Calcula o total de pedidos e o total aprovado para o dia
            $pedidosDia = RifaPay::getOneCompraAtivo($id)
                ->whereBetween('created_at', [$inicioDoDia, $fimDoDia]);

            $valores['totalPedidos'] = $pedidosDia->count(); // Número total de pedidos
            $valores['totalAprovado'] = $pedidosDia->sum('value'); // Valor total aprovado
        }

        return $resultados;
    }

    private function faturamentoPorHora($id) {
        // Inicializa um array de 24 horas com valor 0
        $resultados = array_fill(0, 24, 0);

        // Obtém as compras ativas
        $compraAtivo = RifaPay::getOneCompraAtivo($id);

        foreach ($compraAtivo as $compra) {
            // Extrai a hora do timestamp da compra
            $hora = Carbon::parse($compra->created_at)->format('H');

            // Converte a hora para inteiro
            $horaInt = (int)$hora;

            // Adiciona o valor ao índice correspondente
            if (isset($resultados[$horaInt])) {
                $resultados[$horaInt] += $compra->value;
            }
        }

        return $resultados;
    }






    public function me(Request $request) {
        return response()->json($request->user());
    }

    public function createAfiliado(Request $request) {

        try {
            $client = Clients::findClient($request->cellphone);
            $afiliado = Afiliado::findAfiliado($request->cellphone);

            if(!$client) {
                return response()->json(["success" => false, "msg" =>'Cliente não encontrado!'], 500);
            }
            if($afiliado) {
                return response()->json(["success" => false, "msg" =>'Afiliado já existe!'], 500);
            }
            $afiliado =  Afiliado::createAfiliado($request, $client);

            return response()->json(["success" => true, "data" => 'criado com sucesso'], 201);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }

    }

    public function getAllAfiliado(){
        try {
            $afiliados = Afiliado::findAllAfiliado(['ganhoAfiliado', 'client']);
            if(!$afiliados) {
                return response()->json(["success" => false, "msg" =>'Não tem nenhum afiliado no momento'], 500);
            }
            foreach ($afiliados as $afiliado) {
                $afiliado->totalPedidos = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->count() :  0;
                $afiliado->faturamento = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('faturamento') :  0;
                $afiliado->comissao = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('comissao') :  0;

            }

            return response()->json(["success" => true, "data" =>  $afiliados], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function getOneAfiliado($id){
        try {
            $afiliado = Afiliado::findOneAfiliadoById($id);
            if(!$afiliado) {
                return response()->json(["success" => false, "msg" =>'Afiliado não existe'], 500);
            }
            $afiliado->totalPedidos = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->count() :  0;
            $afiliado->faturamento = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('faturamento') :  0;
            $afiliado->comissao = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('comissao') :  0;

            return response()->json(["success" => true, "data" =>  $afiliado], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
    public function getOneAfiliadoByProduto($idProduto) {
        try {
            // Busca o afiliado que tenha ganho afiliado com o produto específico
            $afiliados = Afiliado::with(['client', 'ganhoAfiliado' => function($query) use ($idProduto) {
                $query->where('rifas_id', $idProduto);
            }])->get();

            if ($afiliados->isEmpty()) {
                return response()->json(["success" => false, "msg" => 'Afiliado não existe'], 500);
            }
            foreach ($afiliados as $afiliado) {
                $afiliado->totalPedidos = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->count() :  0;
                $afiliado->faturamento = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('faturamento') :  0;
                $afiliado->comissao = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('comissao') :  0;

            }



            return response()->json(["success" => true, "data" => $afiliados], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }


    public function afiliadoUpdate(Request $request, $id){
        try {
            $afiliado = Afiliado::findOneAfiliadoById($id);

            if(!$afiliado) {
                return response()->json(["success" => false, "msg" =>'Afiliado não existe'], 500);
            }
            if ($request->link != $afiliado->link && Afiliado::where('link', $request->link)->exists()) {
                return response()->json(["success" => false, "msg" =>'Esse link ja esta cadastrado'], 500);
            }

            $afiliado->update([
                'porcent' => $request->porcent,
                'link' => $request->link,
            ]);


            return response()->json(["success" => true, "data" =>  $afiliado], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function afiliadoFiltro(Request $request) {
        try {
            // Inicializa a consulta usando o modelo Afiliado
            $query = Afiliado::query();

            // Filtro pelo nome
            if ($request->has('name')) {
                $name = $request->input('name');
                $nameParts = explode(' ', $name);

                // Adiciona condição para filtro pelo nome e sobrenome
                $query->whereHas('client', function ($q) use ($nameParts) {
                    if (count($nameParts) > 1) {
                        $q->where('name', 'like', '%' . $nameParts[0] . '%')
                          ->where('surname', 'like', '%' . $nameParts[1] . '%');
                    } else {
                        $q->where('name', 'like', '%' . $nameParts[0] . '%');
                    }
                });
            }

            // Filtro pelo celular
            if ($request->has('cellphone')) {
                $query->where('cellphone', 'like', '%' . $request->input('cellphone') . '%');
            }

            // Obtém os resultados da consulta
            $afiliados = $query->with(['ganhoAfiliado', 'client'])->get();

            // Verifica se a consulta retornou algum resultado
            if ($afiliados->isEmpty()) {
                return response()->json(["success" => false, "msg" => "Clientes não encontrados"], 404);
            }

            // Processa os afiliados para adicionar informações adicionais
            foreach ($afiliados as $afiliado) {
                $afiliado->totalPedidos = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->count() :  0;
                $afiliado->faturamento = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('faturamento') :  0;
                $afiliado->comissao = $afiliado->ganhoAfiliado ? $afiliado->ganhoAfiliado->sum('comissao') :  0;
            }

            // Retorna os resultados
            return response()->json(["success" => true, "data" => $afiliados], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }




    public function sendMessagesWhats(Request $request) {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = $validated['message'];

        // Simulação dos dados fornecidos
        $users = User::whereNotNull('cellphone')->get();

        $client = new Client(['verify' => false]); // Desativa a verificação SSL, considere habilitar em produção

        $headers = [
            'Content-Type' => 'application/json',
            'DeviceToken' => '7459a7f8-9506-416b-b103-78fc0fdfc9d0', // Substitua pelo seu DeviceToken
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3VzLmFwaWJyYXNpbC5jbG91ZC9hdXRoL3JlZ2lzdGVyIiwiaWF0IjoxNzI2NTk1NTEwLCJleHAiOjE3NTgxMzE1MTAsIm5iZiI6MTcyNjU5NTUxMCwianRpIjoibW5YbXdUS3FlQTZBR056dSIsInN1YiI6IjExMzc0IiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.dJvR7wYJyCGLina3AWB14L518MVSfrDJYdKm5L_JlDQ', // Substitua pelo seu Bearer Token
        ];

        $successCount = 0;
        $invalidNumbers = [];
        $otherErrors = []; // Para armazenar outros erros

        foreach ($users as $user) {
            $formattedNumber = preg_replace('/[^0-9]/', '', $user['cellphone']);
            $formattedNumber = '55' . $formattedNumber;

            $body = json_encode([
                'number' => $formattedNumber,
                'text' => $message,
                'time_typing' => 1000,
            ]);

            try {
                $response = $client->post('https://gateway.apibrasil.io/api/v2/whatsapp/sendText', [
                    'headers' => $headers,
                    'body' => $body,
                ]);

                $responseBody = json_decode($response->getBody()->getContents(), true);

                if (isset($responseBody['response']['status']) && $responseBody['response']['status'] === 'error') {
                    if (strpos($responseBody['response']['message'], 'não está registrado no WhatsApp') !== false) {
                        $invalidNumbers[] = $user['cellphone'];
                    } else {
                        // Adiciona outros erros para análise
                        $otherErrors[] = [
                            'number' => $user['cellphone'],
                            'error_message' => $responseBody['response']['message'],
                        ];
                    }
                } else {
                    $successCount++;
                }
            } catch (RequestException $e) {
                $errorResponse = $e->getResponse();
                $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'Erro desconhecido';
                $otherErrors[] = [
                    'number' => $user['cellphone'],
                    'error_message' => $errorBody,
                ];
            }
        }

        return Response::json([
            'success' => "Mensagens enviadas com sucesso para $successCount números!",
            'invalid_numbers' => $invalidNumbers,
            'other_errors' => $otherErrors, // Inclui outros erros no retorno
        ], 200);
    }



    public function deletarCliente($id) {
        try {
            $client = Clients::findClientById($id);
            if (!$client) {
                return response()->json(["success" => false, "msg" => 'Cliente não existe'], 500);
            }

            // Exclui todos os rifas ligados a esse cliente
            $client->rifaNumbers()->delete();

            $client->delete();

            return response()->json(["success" => true, "msg" => 'Cliente deletado com sucesso'], 200);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    public function deletarPedido($id) {
        $pedido = RifaPay::find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $pedido->rifaNumber()->delete();

        $pedido->delete();

        return response()->json(['success' => true, 'message' => 'Pedido excluído com sucesso']);
    }


    public function addPremios(Request $request) {
        try {
            DB::beginTransaction();

            $rifas_id = $request->rifas_id;

            $existingTypeCodes = array_map(fn($t) => $t['code'], $request->reward_types);
            RewardTypes::where('rifas_id', $rifas_id)
                ->whereNotIn('code', $existingTypeCodes)
                ->delete();


            $rewardTypeMap = [];
            foreach ($request->reward_types as $type) {
                $rewardType = RewardTypes::updateOrCreate(
                    [
                        'code' => $type['code'],
                        'rifas_id' => $rifas_id
                    ],
                    [
                        'name' => $type['name'],
                        'is_active' => $type['is_enabled'],
                        'giro_por_usuario' => $type['giro_por_usuario'],
                    ]
                );
                $rewardTypeMap[$type['code']] = $rewardType->id;
            }

            // 2. Remove todos os prêmios antigos desta rifa
            RewardItems::where('rifas_id', $rifas_id)->delete();

            // 3. Salva os novos prêmios
            foreach ($request->reward_items as $item) {
                RewardItems::create([
                    'text' => $item['text'],
                    'type' => $item['type'],
                    'position_number' => $item['position_number'],
                    'min_tries_required' => $item['min_tries_required'],
                    'status' => $item['status'],
                    'metadata' => json_encode($item['metadata'] ?? []),
                    'rifas_id' => $rifas_id,
                    'reward_type_id' => $rewardTypeMap[$item['reward_type_code']],
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'msg' => 'Prêmios salvos com sucesso.']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }



}
