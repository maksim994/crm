<?php

namespace App\Enums;

enum LeadChannel: string
{
    case Form = 'form';
    case Call = 'call';
    case Email = 'email';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Form => 'Заявка',
            self::Call => 'Звонок',
            self::Email => 'Заявка на почту',
            self::Manual => 'Вручную',
        };
    }
}
