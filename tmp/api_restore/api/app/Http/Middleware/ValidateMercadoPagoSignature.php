<?php

namespace App\Http\Middleware;

use Closure;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class ValidateMercadoPagoSignature
{
    private function validateHeaders(Request $request) {
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        if ($xSignature === null) {
            throw new BadRequestException('Missing x-signature header');
        }
        if ($xRequestId === null) {
            throw new BadRequestException('Missing x-request-id header');
        }
        return [$xSignature, $xRequestId];
    }

    private function validateQueryParams(Request $request) {
        if (!$request->has('data.id')) {
            throw new BadRequestException('Missing data.id query param');
        }
        return $request->input('data.id');
    }


    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            [$xSignature, $xRequestId] = $this->validateHeaders($request);
            $dataID = $this->validateQueryParams($request);
            $parts = explode(',', $xSignature);

            $ts = null;
            $hash = null;

            foreach ($parts as $part) {
                $keyValue = explode('=', $part, 2);
                if (count($keyValue) == 2) {
                    $key = trim($keyValue[0]);
                    $value = trim($keyValue[1]);
                    if ($key === "ts") {
                        $ts = $value;
                    } elseif ($key === "v1") {
                        $hash = $value;
                    }
                }
            }

            $secret = env('MERCADO_PAGO_WEBHOOK_SECRET');

            $manifest = "id:$dataID;request-id:$xRequestId;ts:$ts;";

            $sha = hash_hmac('sha256', $manifest, $secret);
            if ($sha === $hash) {
                return $next($request);
            } else {
                return response()->json(["message" => "Invalid credentials"], 401);
            }
            
            return $next($request);
        } catch (BadRequestException $th) {
            return response()->json(["message" => $th->getMessage()], 400);
        } catch (Exception $e) {
            return response()->json(["message" => "Internal Error"], 500);
        }
    }
}
