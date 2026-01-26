<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'is_enabled',
        'environment',
        'api_url',
        'client_id',
        'client_secret',
        'username',
        'password',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'additional_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'client_secret' => 'encrypted',
            'password' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'additional_settings' => 'array',
        ];
    }

    /**
     * Get the default API URL based on environment
     */
    public function getDefaultApiUrl(): string
    {
        if ($this->provider === 'factus') {
            return $this->environment === 'production'
                ? 'https://api.factus.com.co'
                : 'https://api-sandbox.factus.com.co';
        }

        return '';
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if billing is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->client_id) 
            && !empty($this->client_secret)
            && !empty($this->username)
            && !empty($this->password);
    }

    /**
     * Get or create the singleton settings instance
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate(
            ['provider' => 'factus'],
            [
                'is_enabled' => false,
                'environment' => 'sandbox',
            ]
        );
    }
}
