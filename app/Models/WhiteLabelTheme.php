<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'theme_name',
        'layout_config',
        'component_styles',
        'responsive_settings',
        'custom_css',
        'custom_js',
        'widget_config',
        'is_active'
    ];

    protected $casts = [
        'layout_config' => 'array',
        'component_styles' => 'array',
        'responsive_settings' => 'array',
        'widget_config' => 'array',
        'is_active' => 'boolean'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function getCompiledCssAttribute(): string
    {
        $css = "";

        // Add component styles
        if ($this->component_styles) {
            foreach ($this->component_styles as $component => $styles) {
                if (is_array($styles)) {
                    $css .= ".{$component} {\n";
                    foreach ($styles as $property => $value) {
                        $css .= "  {$property}: {$value};\n";
                    }
                    $css .= "}\n\n";
                }
            }
        }

        // Add responsive styles
        if ($this->responsive_settings) {
            foreach ($this->responsive_settings as $breakpoint => $styles) {
                $css .= "@media (max-width: {$breakpoint}) {\n";
                foreach ($styles as $selector => $properties) {
                    $css .= "  {$selector} {\n";
                    foreach ($properties as $property => $value) {
                        $css .= "    {$property}: {$value};\n";
                    }
                    $css .= "  }\n";
                }
                $css .= "}\n\n";
            }
        }

        // Add custom CSS
        if ($this->custom_css) {
            $css .= $this->custom_css;
        }

        return $css;
    }

    public function getLayoutClass(string $page): string
    {
        $config = $this->layout_config ?? [];
        return $config['pages'][$page]['class'] ?? $config['default']['class'] ?? 'default-layout';
    }

    public function getWidgetConfig(string $area): array
    {
        $config = $this->widget_config ?? [];
        return $config[$area] ?? [];
    }

    public function hasCustomLayout(): bool
    {
        return !empty($this->layout_config);
    }

    public function hasCustomStyles(): bool
    {
        return !empty($this->component_styles) || !empty($this->custom_css);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}