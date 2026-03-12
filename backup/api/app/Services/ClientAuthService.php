<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

use App\Models\V1\{Clients};

class ClientAuthService
{
public function attempt($cellphone)
{
    $client = Clients::where('cellphone', $cellphone)->first();
    if (!$client) {
        return false;
    }

    $loginResult = Auth::guard('clients')->login($client);

    return $loginResult;
}


    public function check()
    {
        return Auth::guard('clients')->check();
    }

    public function logout()
    {
        Auth::guard('clients')->logout();
    }
}
