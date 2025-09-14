<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhiteLabelMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'title',
        'url',
        'target',
        'icon',
        'sort_order',
        'is_active',
        'menu_location',
        'parent_id',
        'visibility_rules'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'visibility_rules' => 'array'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelMenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WhiteLabelMenuItem::class, 'parent_id');
    }

    public function getIsExternalAttribute(): bool
    {
        return str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://');
    }

    public function getFullUrlAttribute(): string
    {
        if ($this->is_external) {
            return $this->url;
        }

        // Handle relative URLs
        if (str_starts_with($this->url, '/')) {
            return $this->client->url . $this->url;
        }

        return $this->client->url . '/' . $this->url;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('menu_location', $location);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function isVisible(array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $rules = $this->visibility_rules;
        if (empty($rules)) {
            return true;
        }

        // Check visibility rules
        foreach ($rules as $rule) {
            $type = $rule['type'] ?? '';
            $condition = $rule['condition'] ?? '';
            $value = $rule['value'] ?? '';

            switch ($type) {
                case 'user_type':
                    $userType = $context['user_type'] ?? 'guest';
                    if ($condition === 'equals' && $userType !== $value) {
                        return false;
                    }
                    break;

                case 'page_type':
                    $pageType = $context['page_type'] ?? '';
                    if ($condition === 'equals' && $pageType !== $value) {
                        return false;
                    }
                    break;

                case 'feature':
                    $hasFeature = $context['features'][$value] ?? false;
                    if ($condition === 'enabled' && !$hasFeature) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}