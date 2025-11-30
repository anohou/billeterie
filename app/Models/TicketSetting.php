<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSetting extends Model
{
    protected $fillable = [
        'company_name',
        'phone_numbers',
        'footer_messages',
        'qr_code_base_url',
        'print_qr_code',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'footer_messages' => 'array',
        'print_qr_code' => 'boolean',
    ];

    /**
     * Get the ticket settings (singleton pattern)
     */
    public static function getSettings()
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'TSR CI',
                'phone_numbers' => ['+225 XX XX XX XX XX', '+225 XX XX XX XX XX'],
                'footer_messages' => ['Valable pour ce voyage', 'Non remboursable'],
                'qr_code_base_url' => null,
                'print_qr_code' => true,
            ]
        );
    }
}
