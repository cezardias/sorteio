<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\V1\Clients;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class AuthenticateClient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtém o token do cabeçalho Authorization
        $token = $request->header('Authorization');

        // Verifica se o token está presente
        if (!$token) {
            return response()->json(['error' => 'Token não fornecido'], 401);
        }

        // Remover o prefixo "Bearer " do token se estiver presente
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        // Validar o token (aqui você pode usar sua lógica de validação, como JWT, OAuth, etc.)
        try {
            // Substitua esta parte com sua lógica de validação de token
            // Por exemplo, usando JWT:
            // dd(env('JWT_SECRET'));
            $credentials = JWT::decode($token, new Key( env('JWT_SECRET'), 'HS256'));
            // Se necessário, busque o cliente no banco de dados
            $client = Clients::find($credentials->sub);

            // Se o cliente não for encontrado ou inválido, retornar 401
            if (!$client) {
                return response()->json(['error' => 'Token inválido'], 401);
            }

            // Autenticar o cliente (opcional, dependendo da implementação)
            Auth::login($client);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        // Prosseguir com a requisição
        return $next($request);
    }
}
