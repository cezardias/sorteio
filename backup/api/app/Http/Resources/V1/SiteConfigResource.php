<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'metaPixel' => $this->meta_pixel,
            'instagramLink' => $this->instagram_link,
            'whatsappLink' => $this->whatsapp_link,
            'urlLogoSite' => $this->url_logo_site,
            'urlFaviconSite' => $this->url_favicon_site,
            'siteName' => $this->site_name,
            'plataformName' => $this->plataform_name,
        ];
    }
}
