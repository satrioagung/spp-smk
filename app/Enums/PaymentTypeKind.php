<?php

namespace App\Enums;

enum PaymentTypeKind: string
{
    case BULANAN = 'bulanan';
    case TAHUNAN = 'tahunan';
    case SEKALI = 'sekali';
    case BEBAS = 'bebas';

    public function label(): string
    {
        return match ($this) {
            self::BULANAN => 'Bulanan',
            self::TAHUNAN => 'Tahunan',
            self::SEKALI => 'Sekali',
            self::BEBAS => 'Bebas',
        };
    }
}
