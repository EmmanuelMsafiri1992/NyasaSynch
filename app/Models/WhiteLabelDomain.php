<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_client_id',
        'domain',
        'status',
        'ssl_status',
        'dns_records',
        'verified_at',
        'ssl_issued_at',
        'cloudflare_zone_id',
        'verification_errors'
    ];

    protected $casts = [
        'dns_records' => 'array',
        'verification_errors' => 'array',
        'verified_at' => 'datetime',
        'ssl_issued_at' => 'datetime'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(WhiteLabelClient::class, 'white_label_client_id');
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->status === 'verified';
    }

    public function getHasSslAttribute(): bool
    {
        return $this->ssl_status === 'active';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'verified' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'inactive' => 'gray',
            default => 'gray'
        };
    }

    public function getSslStatusColorAttribute(): string
    {
        return match($this->ssl_status) {
            'active' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    public function getRequiredDnsRecords(): array
    {
        return [
            [
                'type' => 'CNAME',
                'name' => $this->domain,
                'value' => config('app.domain', 'yourdomain.com'),
                'description' => 'Points your domain to our platform'
            ],
            [
                'type' => 'TXT',
                'name' => '_verification.' . $this->domain,
                'value' => 'nyasajob-verification=' . $this->getVerificationToken(),
                'description' => 'Verifies domain ownership'
            ]
        ];
    }

    public function getVerificationToken(): string
    {
        return hash('sha256', $this->domain . config('app.key'));
    }

    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verification_errors' => null
        ]);
    }

    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'verification_errors' => $errors
        ]);
    }

    public function activateSSL(): void
    {
        $this->update([
            'ssl_status' => 'active',
            'ssl_issued_at' => now()
        ]);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWithSSL($query)
    {
        return $query->where('ssl_status', 'active');
    }
}