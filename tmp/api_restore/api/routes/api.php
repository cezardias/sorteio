<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\V1\{AuthController, AdminController, ClientController, MercadoPagoController, RifasController, PixController, SiteConfigController};
    use App\Http\Controllers\{RewardAdminController, RewardPublicController};

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "api" middleware group. Make something great!
    |
    */

    Route::group(['prefix' => 'client'], function () {
        Route::post("/cadastro", [AuthController::class, "register"])->name('client.register');
        Route::post("/login", [AuthController::class, "login"])->name('client.login');
        Route::get("/login", [AuthController::class, "logar"])->name('login');
        Route::middleware('auth.client')->post("/logout", [AuthController::class, "logout"])->name('client.logout');

        Route::middleware('auth.client')->get("/checkout/pedido/{id}", [RifasController::class, "getCompra"])->where(['id' => '[0-9]+'])->name('checkout.pedido');

        Route::middleware('auth.client')->get("/meus-pedidos/sorteios/{id}", [RifasController::class, "getCompraClient"])->where(['id' => '[0-9]+'])->name('client.pedidos');
    });

    Route::group(['prefix' => 'admin'], function () {
        Route::post("/user/register", [AdminController::class, "storeUser"])->name('admin.create.user');
        Route::post("/user/login", [AdminController::class, "login"])->name('admin.login.user');
        Route::middleware('auth:sanctum')->post("/user/logout", [AdminController::class, "logout"])->name('admin.logout.user');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/todas-rifas", [RifasController::class, "getAllRifasAdmin"])->name('admin.all.rifas');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/todas-rifas/filtro", [RifasController::class, "getAllRifasAdminFiltro"])->name('admin.all.rifas');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post('/dashboard/cliente/deletar/{id}',  [AdminController::class, 'deletarCliente'])->name('admin.cliente.deletar');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post('/dashboard/deletar/pedido/{id}',  [AdminController::class, 'deletarPedido']);




        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/rifas/cadastrar", [RifasController::class, "storeRifa"])->name('admin.create.rifa');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/rifa/editar/{id}", [RifasController::class, "getOneRifa"])->name('admin.get.edit.rifa');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/rifa/editar/{id}", [RifasController::class, "editRifa"])->name('admin.edit.rifa');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/rifa/excluir/{id}", [RifasController::class, "excluirRifa"])->name('admin.excluit.rifa');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/send-messages/whats", [AdminController::class, "sendMessagesWhats"])->name('admin.send.messages.whats');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/bilhete-premiado/cadastrar", [RifasController::class, "storeBilhetePremiado"])->name('admin.create.rifa.bilhete-premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/bilhete-premiado/all/{id}", [RifasController::class, "getAllBilhetePremiado"])->name('admin.get.rifa.bilhete-premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/bilhete-premiado/filtro/{id}", [RifasController::class, "getBilhetePremiadoFiltro"])->name('admin.get.rifa.bilhete-premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/bilhete-premiado/editar/{id}", [RifasController::class, "getOneBilhetePremiado"])->name('admin.get.rifa.bilhete-premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/bilhete-premiado/editar", [RifasController::class, "editarBilhetePremiado"])->name('admin.get.rifa.bilhete-premiado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/bilhete-premiado/delete/{id}/{rifaId}", [RifasController::class, "destroyBilhetePremiado"])->name('admin.delete.rifa.bilhete-premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/todos-pacotes/{id}", [RifasController::class, "getAllPacotes"])->name('admin.pacote');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/pacote/{id}", [RifasController::class, "getOnePacotes"])->name('admin.pacote');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/pacote/cadastrar", [RifasController::class, "storePacote"])->name('admin.create.rifa.pacote');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/pacote/filtro/{id}", [RifasController::class, "filtroPacotes"])->name('admin.create.rifa.pacote');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/pacotes/editar", [RifasController::class, "editarPacote"])->name('admin.create.rifa.pacote');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/pacotes/deletar/{id}", [RifasController::class, "deletePacote"])->name('admin.create.rifa.pacote');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/rifa/imagens/{id}", [RifasController::class, "getImagens"])->name('admin.pegar.rifa.imagem');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/rifa/imagens/cadastrar", [RifasController::class, "storeImagem"])->name('admin.create.rifa.imagem');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/rifa/imagens/deletar/{id}/", [RifasController::class, "destroyImagem"])->where(['id' => '[0-9]+'])->name('admin.deletar.rifa.imagem');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/upsell/{id}", [RifasController::class, "getUpsellRifa"])->name('admin.get.rifa.upsell');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/upsell/cadastrar", [RifasController::class, "storeUpsell"])->name('admin.create.rifa.upsell');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/rifa/finalizar/{id}", [RifasController::class, "finalizarRifa"])->name('admin.finalizar.rifa');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/rifa/ativar/{id}", [RifasController::class, "ativarRifa"])->name('admin.ativar.rifa');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/me", [AdminController::class, "me"])->name('admin.me.user');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/client/procurar/pelo-telefone", [AdminController::class, "procurarClientCellphone"])->name('admin.procurar.client.celular');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/rifa/procurar-numero-premiado/procurar-ganhador", [AdminController::class, "procurarGanhadorPeloNumero"])->name('admin.procurar.ganhador');


        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/rifa/definir-ganhador", [AdminController::class, "definirGanhador"])->name('admin.definir.ganhador');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/cadastrar/ganhador/{id}", [AdminController::class, "getOneGanhador"])->name('admin.pegar.um.ganhador');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/cadastrar/ganhador", [AdminController::class, "cadastrarGanhador"])->name('admin.cadastrar.ganhador');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/editar/ganhador", [AdminController::class, "editarGanhador"])->name('admin.editar.ganhador');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/delete/ganhador/{id}", [AdminController::class, "destroyGanhador"])->name('admin.delete.ganhador');


        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/client/rifa/adicionar-numero", [AdminController::class, "adicionarNumerosRifas"])->name('admin.adicionar.numero');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/client/rifa/adicionar/bilhete-premiado", [AdminController::class, "adicionarBilhetePremiado"])->name('admin.adicionar.bilhete.premiado');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/client/rifa/ativas", [AdminController::class, "rifaAtivas"])->name('admin.adicionar.bilhete.premiado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/consulta-cota/{id}", [AdminController::class, "consultaCota"])->name('admin.adicionar.bilhete.premiado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/consulta-cota/consulta-cota/min-max", [AdminController::class, "consultaCotaMinAndMax"])->name('admin.adicionar.bilhete.premiado.min.max');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/pedidos", [AdminController::class, "getPedidos"])->name('admin.pedidos');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post('/dashboard/rifas/{rifas_id}/filtro', [AdminController::class, 'filtroRifas'])->name('admin.filtro.rifas');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/pedidos/filtro", [AdminController::class, "getPedidosFiltro"])->name('admin.pedidos');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/pedido/{idRifa}/{idClient}", [AdminController::class, "getOnePedidos"])->name('admin.one.pedidos');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/deletar/pedido/{id}", [AdminController::class, "cancelarPedidos"])->name('admin.cancelar.pedidos');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/aprovar/pedido/{id}", [AdminController::class, "aprovarPedidos"])->name('admin.aprovar.pedidos');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/todos/clientes", [AdminController::class, "allClients"])->name('admin.todos.clientes');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/todos/clientes/filtro", [AdminController::class, "allClientsFiltro"])->name('admin.filtro.clientes');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/editar/cliente", [AdminController::class, "editarClients"])->name('admin.editar.cliente');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/ranking-geral", [AdminController::class, "rankingGeral"])->name('admin.ranking.cliente');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/ranking-geral/filtro", [AdminController::class, "rankingGeralFiltro"])->name('admin.ranking.cliente');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/todos/usuarios", [AdminController::class, "getAllUsers"])->name('admin.usuarios');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/todos/usuarios/filtro", [AdminController::class, "getAllUsersFiltro"])->name('admin.usuarios');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/usuario/{id}", [AdminController::class, "getOneUser"])->name('admin.usuarios');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/usuarios/editar", [AdminController::class, "editarUsers"])->name('admin.editar.usuarios');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/usuarios/deletar/{id}", [AdminController::class, "destroyUser"])->name('admin.editar.usuarios');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/payment", [AdminController::class, "getAllGateway"])->name('admin.get.gateway');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/payment/{id}", [AdminController::class, "showGateway"])->name('admin.get.one.gateway');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/payment/make", [AdminController::class, "storeGateway"])->name('admin.make.gateway');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/payment/update", [AdminController::class, "updateGateway"])->name('admin.editar.gateway');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->delete("/dashboard/payment/delete", [AdminController::class, "destroyGateway"])->name('admin.editar.gateway');


        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/site-settings/editar", [AdminController::class, "storeConfigSite"])->name('admin.edit.site-config');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/vendas", [AdminController::class, "getVendas"])->name('admin.get.vendas');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/vendas/filtro", [AdminController::class, "vendasFiltro"])->name('admin.filtro.vendas');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/one/venda/{id}", [AdminController::class, "getOneVendas"])->name('admin.one.vendas');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/one/venda/filtro/{id}", [AdminController::class, "vendasFiltroOne"])->name('admin.filtro.one.vendas');

        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/afiliado/create", [AdminController::class, "createAfiliado"])->name('admin.create.afiliado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/todos/afiliados/", [AdminController::class, "getAllAfiliado"])->name('admin.get.all.afiliados');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/one/afiliado/{id}", [AdminController::class, "getOneAfiliado"])->name('admin.get.one.afiliado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->get("/dashboard/one/afiliado/produto/{idProduto}", [AdminController::class, "getOneAfiliadoByProduto"])->name('admin.get.one.afiliado.by.produto');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->put("/dashboard/afiliado/update/{id}", [AdminController::class, "afiliadoUpdate"])->name('admin.update.afiliado');
        Route::middleware(['auth:sanctum', 'checkAdmin:admin,superadmin'])->post("/dashboard/afiliado/filtro/", [AdminController::class, "afiliadoFiltro"])->name('admin.filtro.afiliado');
    });
    Route::group(['prefix' => 'produtos'], function () {
        Route::get("/", [RifasController::class, "allRifas"])->name('all.rifas');
        Route::get("/{slug}/{id}/{afiliadoId?}", [RifasController::class, "show"])->where(['slug' => '[a-zA-Z0-9\-_]+', 'id' => '[0-9]+'])->name('show.one.rifa');

        Route::get("/todos/ganhadores", [RifasController::class, "getAllWinners"])->name('admin.pegar.ganhador');

        Route::post("/comprar-rifa/{afiliadoId?}", [RifasController::class, "buyRifa"])->name('buy.rifa');
        Route::get("/payment-status/{paymentId}", [RifasController::class, "checkPaymentStatus"])->name('check.pagamento.rifa');

    });

    Route::get("/index", [RifasController::class, "index"])->name('index');
    Route::get("/get-all-numeros-premiados/{id}", [RifasController::class, "getNumerosPremiados"]);



    Route::group(["prefix" => "public-rifas"], function () {
        Route::get("/latest", [RifasController::class, "latest"]);
        Route::get("/latest-winner", [RifasController::class, "getLatestWinner"]);
    });

    Route::get("/dashboard/site-settings", [AdminController::class, "getConfigSite"])->name('admin.get.site-config');

    Route::post("/get-numbers", [ClientController::class, "getNumbers"]);

    Route::get("/config", [SiteConfigController::class, "getUserSiteConfig"]);
    Route::post("/pix", [PixController::class, "index"]);
    Route::post("/pay-cota", [MercadoPagoController::class, "getQr"]);
    Route::post("/mercado-pago-payments", [MercadoPagoController::class, 'index']);


    // routes/api.php
    Route::get('/rifas/{rifa}/rewards/config', [RewardPublicController::class,'config']);

    Route::middleware('auth.client')->group(function () { // <<< troquei sanctum por auth.client
        Route::get ('/rifas/{rifa}/rewards/balances', [RewardPublicController::class,'balances']);
        Route::get ('/rifas/{rifa}/rewards/summary',  [RewardPublicController::class,'summary']);
        Route::post('/rifas/{rifa}/rewards/{type}/redeem', [RewardPublicController::class,'redeem']);
    });

    // Admin
    Route::middleware('auth:sanctum', 'checkAdmin:admin,superadmin')->get ('/admin/rewards/{rifa}', [RewardAdminController::class,'show']);
    Route::middleware('auth:sanctum', 'checkAdmin:admin,superadmin')->post('/admin/rewards/{rifa}', [RewardAdminController::class,'store']);


