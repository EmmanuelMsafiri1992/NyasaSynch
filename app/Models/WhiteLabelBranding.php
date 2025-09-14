<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelBranding extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'site_name',
        'site_tagline',
        'site_description',
        'logo_url',
        'favicon_url',
        'login_logo_url',
        'email_logo_url',
        'color_scheme',
        'typography',
        'custom_css',
        'footer_content',
        'email_templates',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'social_links',
        'google_analytics_id',
        'facebook_pixel_id',
        'custom_head_code',
        'custom_body_code'
    ];

    protected $casts = [
        'color_scheme' => 'array',
        'typography' => 'array',
        'custom_css' => 'array',
        'footer_content' => 'array',
        'email_templates' => 'array',
        'social_links' => 'array'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function getPrimaryColorAttribute(): string
    {
        return $this->color_scheme['primary'] ?? '#007bff';
    }

    public function getSecondaryColorAttribute(): string
    {
        return $this->color_scheme['secondary'] ?? '#6c757d';
    }

    public function getAccentColorAttribute(): string
    {
        return $this->color_scheme['accent'] ?? '#28a745';
    }

    public function getGeneratedCssAttribute(): string
    {
        $css = ":root {\n";

        if ($this->color_scheme) {
            foreach ($this->color_scheme as $key => $color) {
                $css .= "  --color-{$key}: {$color};\n";
            }
        }

        if ($this->typography) {
            foreach ($this->typography as $key => $value) {
                $css .= "  --font-{$key}: {$value};\n";
            }
        }

        $css .= "}\n\n";

        // Add custom CSS rules
        if ($this->custom_css && is_array($this->custom_css)) {
            foreach ($this->custom_css as $rule) {
                $css .= $rule . "\n";
            }
        }

        return $css;
    }

    public function hasLogo(): bool
    {
        return !empty($this->logo_url);
    }

    public function hasFavicon(): bool
    {
        return !empty($this->favicon_url);
    }

    public function hasCustomColors(): bool
    {
        return !empty($this->color_scheme);
    }

    public function hasCustomFonts(): bool
    {
        return !empty($this->typography);
    }

    public function getSocialLink(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
    }

    public function setSocialLink(string $platform, string $url): void
    {
        $links = $this->social_links ?? [];
        $links[$platform] = $url;
        $this->update(['social_links' => $links]);
    }
}