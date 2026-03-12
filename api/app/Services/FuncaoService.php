<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Auth;
class FuncaoService
{
    function convertToDecimal($price) {

        return floatval($price);
    }




}
