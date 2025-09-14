<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsFieldMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'entity_type',
        'local_field',
        'ats_field',
        'field_type',
        'transformation_rules',
        'is_required',
        'default_value'
    ];

    protected $casts = [
        'transformation_rules' => 'array',
        'is_required' => 'boolean'
    ];

    public function atsConnection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class);
    }

    public function getFieldTypeDisplayAttribute(): string
    {
        return match($this->field_type) {
            'string' => 'Text',
            'number' => 'Number',
            'boolean' => 'True/False',
            'date' => 'Date',
            'array' => 'List',
            'object' => 'Object',
            default => ucfirst($this->field_type)
        };
    }

    public function getEntityTypeDisplayAttribute(): string
    {
        return match($this->entity_type) {
            'job' => 'Job Posting',
            'candidate' => 'Candidate',
            'application' => 'Application',
            default => ucfirst($this->entity_type)
        };
    }

    public function applyTransformation($value)
    {
        if (!$this->transformation_rules || !is_array($this->transformation_rules)) {
            return $this->castValue($value);
        }

        foreach ($this->transformation_rules as $rule) {
            $value = $this->applyRule($value, $rule);
        }

        return $this->castValue($value);
    }

    private function applyRule($value, array $rule)
    {
        $type = $rule['type'] ?? 'none';

        return match($type) {
            'replace' => str_replace($rule['search'] ?? '', $rule['replace'] ?? '', $value),
            'regex' => preg_replace($rule['pattern'] ?? '', $rule['replacement'] ?? '', $value),
            'uppercase' => strtoupper($value),
            'lowercase' => strtolower($value),
            'trim' => trim($value),
            'date_format' => $this->formatDate($value, $rule['from'] ?? '', $rule['to'] ?? 'Y-m-d'),
            'map_values' => $rule['mapping'][$value] ?? $value,
            'split' => explode($rule['delimiter'] ?? ',', $value),
            'join' => is_array($value) ? implode($rule['delimiter'] ?? ',', $value) : $value,
            'extract_number' => (float) preg_replace('/[^0-9.]/', '', $value),
            'boolean' => in_array(strtolower($value), ['true', '1', 'yes', 'y', 'on']),
            default => $value
        };
    }

    private function formatDate($value, string $fromFormat, string $toFormat): string
    {
        try {
            if (empty($fromFormat)) {
                // Try to parse common date formats
                $date = \Carbon\Carbon::parse($value);
            } else {
                $date = \Carbon\Carbon::createFromFormat($fromFormat, $value);
            }
            return $date->format($toFormat);
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function castValue($value)
    {
        return match($this->field_type) {
            'string' => (string) $value,
            'number' => is_numeric($value) ? (float) $value : null,
            'boolean' => (bool) $value,
            'date' => $this->parseDate($value),
            'array' => is_array($value) ? $value : [$value],
            'object' => is_array($value) || is_object($value) ? $value : null,
            default => $value
        };
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateValue($value): bool
    {
        if ($this->is_required && empty($value)) {
            return false;
        }

        return match($this->field_type) {
            'string' => true,
            'number' => is_numeric($value),
            'boolean' => in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']),
            'date' => $this->isValidDate($value),
            'array' => is_array($value) || is_string($value),
            'object' => is_array($value) || is_object($value),
            default => true
        };
    }

    private function isValidDate($value): bool
    {
        try {
            \Carbon\Carbon::parse($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function getValueOrDefault($value)
    {
        if (empty($value) && !empty($this->default_value)) {
            return $this->default_value;
        }

        return $value;
    }
}