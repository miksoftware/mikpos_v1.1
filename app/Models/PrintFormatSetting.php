<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintFormatSetting extends Model
{
    protected $fillable = [
        'document_type',
        'display_name',
        'format',
        'letter_options',
    ];

    protected function casts(): array
    {
        return [
            'letter_options' => 'array',
        ];
    }

    public const DEFAULT_LETTER_OPTIONS = [
        'show_business' => true,
        'show_customer' => true,
        'show_sale_info' => true,
        'show_payment_info' => true,
        'show_amount_words' => true,
        'show_footer' => true,
    ];

    public static function getFormat(string $documentType): string
    {
        $setting = static::where('document_type', $documentType)->first();
        return $setting?->format ?? '80mm';
    }

    public static function getLetterOptions(string $documentType): array
    {
        $setting = static::where('document_type', $documentType)->first();
        return array_merge(self::DEFAULT_LETTER_OPTIONS, $setting?->letter_options ?? []);
    }
}
