<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhiteLabelPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'type',
        'is_published',
        'show_in_menu',
        'menu_order',
        'template',
        'custom_fields'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_menu' => 'boolean',
        'menu_order' => 'integer',
        'custom_fields' => 'array'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function getUrlAttribute(): string
    {
        return $this->client->url . '/page/' . $this->slug;
    }

    public function getExcerptAttribute(): string
    {
        $stripped = strip_tags($this->content);
        return Str::limit($stripped, 200);
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('menu_order')->orderBy('title');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}