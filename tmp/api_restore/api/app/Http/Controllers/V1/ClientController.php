<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ItemNotFoundException;

use App\Models\V1\{Clients};


class ClientController extends Controller
{
    public function getNumbers(Request $request) {
        try {
            $client = Clients::select('id', 'phone', 'name')->where('phone', $request->phone)->first();
            if (!isset($client)) {
                throw new ItemNotFoundException('Telefone não cadastrado');
            }
            $clientId = $client->id;
            $info = DB::select("SELECT cotas.id, rifas.thumbnail, rifas.title ,cotas.payment_status, cotas.created_at, cotas.price, count(rifa_numbers.number) as numbers_quant, GROUP_CONCAT(rifa_numbers.number) as numbers FROM rifa_numbers INNER JOIN cotas ON rifa_numbers.cota_id = cotas.id INNER JOIN rifas ON rifa_numbers.rifa_id = rifas.id WHERE rifa_numbers.client_id = $clientId AND payment_status IN (0,1,2,3,10) GROUP BY cotas.id,rifa_numbers.rifa_id");
            return response()->json(["success" => true, "data" => ["orders" => $info, "client" => $client]], 200);
        } catch (ItemNotFoundException $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 404);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }


}
