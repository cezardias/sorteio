<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Models\V1\{Clients};
use App\Http\Requests\V1\{ClientRequest};

class AuthController extends Controller
{

    protected $postSuccess = 201;
    protected $serverError = 500;

    public function __construct()
    {
        // $this->middleware('auth:clients', ['except' => ['login']]);
    }


    public function register(ClientRequest $request) {
        try {
            $name = $request->name;
            $surname = $request->surname;
            $cellphone = $request->cellphone;
            $client = Clients::createClient($name, $surname, $cellphone);

            return $client
                ? response()->json(['message' => 'Novo cliente criado.'], 201)
                : response()->json(['message' => 'Cliente ja existente.'], 400);

        } catch (\Throwable $th) {
            return response()->json(['message' => 'Erro interno'], 500);
        }
    }

    public function logar() {
        return response()->json(['message' => 'Você precisa se logar.'], 403 );
    }


    public function login(Request $request) {
        $credentials = $request->only('cellphone');

        $client = Clients::where('cellphone', $credentials['cellphone'])->first();

        if (!$client) {
            return response()->json(['error' => 'invalid_credentials'], 400);
        }

        try {
            if (!$token = JWTAuth::fromUser($client)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token', 'error' => $e->getMessage()], 500);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token) {
        return response()->json([
            'access_token' => $token,
            // 'token_type' => 'bearer',
            // 'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    public function logout(Request $request) {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logout realizado com sucesso.']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_invalidate_token'], 500);
        }
    }

    public function someProtectedMethod() {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        return 'O usuário está autenticado';
    }

}
