<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_title',
        'logo_dark',
        'logo_light',
        'footer_company',
        'google_analytics',
        'webhook_url',
        'product_title',
        'product_subtitle',
        'author',
        'tags',
        'share_title',
        'share_image',
        'share_description',
        'whatsapp',
        'whatsapp_group_url',
        'instagram',
        'helpdesk_url',
        'email'
    ];
}
