<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreSiteConfigRequest;
use App\Http\Resources\V1\SiteConfigResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Exception;
use Throwable;

use App\Models\User;
use App\Models\V1\SiteConfig;

class SiteConfigController extends Controller
{
    public function getUserSiteConfig() {
        try {
            $configData = SiteConfig::select('meta_pixel', 'instagram_link', 'whatsapp_link', 'url_logo_site', 'url_favicon_site', 'site_name', 'plataform_name')->where("id", "1")->first();
            if (!$configData) {
                return response()->json(["success" => true], 200);
            }

            return response()->json(["success" => true, "data" => $configData], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    private function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');
        $contents = File::get($envFile);
        $lines = explode("\n", $contents);
        foreach ($lines as &$line) {
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            $key = $parts[0];
            if (isset($data[$key])) {
                $line = $key . '=' . $data[$key];
                unset($data[$key]);
            }
        }
        $updatedContents = implode("\n", $lines);
        File::put($envFile, $updatedContents);
    }

    private function parseMetaPixel($pixel) {
        if (is_numeric($pixel)) {
            return $pixel;
        } else {
            $start = strrpos($pixel, '?id=');
            $end = strrpos(substr($pixel, $start), '&ev=');
            $str = substr($pixel, $start, $end);
            return preg_replace('/[^0-9]/', '', $str);
        }
    }

    public function index()
    {
        try {
            $configData = SiteConfig::where("id", "1")->first();
            if (!$configData) {
                return response()->json(["success" => true], 200);
            }
            $configData->email = User::orderBy("updated_at", "desc")->first()->email;
            $configData->mercadoPagoAccessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
            $configData->mercadoPagoPublic = env('MERCADO_PAGO_PUBLIC_KEY');

            return response()->json(["success" => true, "data" => $configData], 200);
        } catch (Exception $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteConfigRequest $request)
    {
        try {
            $pixel = $this->parseMetaPixel($request->pixel);
            $dataArray = [
                'meta_pixel' => $pixel,
                'instagram_link' => $request->linkinstasite,
                'whatsapp_link' => $request->linkwppsite,
                'site_name' => $request->nomesite,
                'plataform_name' => $request->plataforma,
            ];
            if ($request->file("faviconsite")) {
                $favicon = $request->file("faviconsite");
                $uniqueFaviconFileName = uniqid() . '.' . $favicon->getClientOriginalExtension();
                $favicon->move(public_path("assets/images/favicon"), $uniqueFaviconFileName);
                $faviconRelativePath = "assets/images/favicon/" . $uniqueFaviconFileName;
                $faviconUrl = asset($faviconRelativePath);
                $dataArray['url_favicon_site'] = $faviconUrl;
            }
            if ($request->file("logosite")) {
                $logo = $request->file("logosite");
                $uniqueLogoFileName = uniqid() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path("assets/images/logo"), $uniqueLogoFileName);
                $logoRelativePath = "assets/images/logo/" . $uniqueLogoFileName;
                $logoUrl = asset($logoRelativePath);
                $dataArray['url_logo_site'] = $logoUrl;
            }
            $this->updateEnvFile(['MERCADO_PAGO_ACCESS_TOKEN' => str_replace(" ", '', $request->secretmercadopago), 'MERCADO_PAGO_PUBLIC_KEY' => str_replace(" ",'',$request->publickeymercado)]);

            $newSiteConfigData = SiteConfig::updateOrCreate(['id' => 1], $dataArray);

            if ($request->password) {
                if ($request->email) {
                    User::where('id', '>=',1)->update(['password' => Hash::make($request->password), 'email' => $request->email]);
                } else {
                    User::where('id', '>=',1)->update(['password' => Hash::make($request->password)]);
                }
            }

            return response()->json(["success" => true, "data" => new SiteConfigResource($newSiteConfigData)], 200);
        } catch (Throwable $e) {
            return response()->json(["success" => false, "msg" => $e->getMessage()], 500);
        }
    }
}
