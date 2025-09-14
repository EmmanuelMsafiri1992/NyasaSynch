<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelEmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'template_key',
        'subject',
        'html_content',
        'text_content',
        'variables',
        'is_active',
        'from_name',
        'from_email',
        'reply_to',
        'attachments'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'attachments' => 'array'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function render(array $data = []): array
    {
        $subject = $this->renderTemplate($this->subject, $data);
        $htmlContent = $this->renderTemplate($this->html_content, $data);
        $textContent = $this->text_content ? $this->renderTemplate($this->text_content, $data) : strip_tags($htmlContent);

        return [
            'subject' => $subject,
            'html' => $htmlContent,
            'text' => $textContent,
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'reply_to' => $this->reply_to,
            'attachments' => $this->attachments ?? []
        ];
    }

    private function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, (string) $value, $rendered);
        }

        // Handle nested data
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $placeholder = '{{' . $key . '.' . $subKey . '}}';
                    $rendered = str_replace($placeholder, (string) $subValue, $rendered);
                }
            }
        }

        return $rendered;
    }

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('template_key', $key);
    }
}