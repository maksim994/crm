<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NotProcessed = 'not_processed';
    case NoAnswer = 'no_answer';
    case PreparingOffer = 'preparing_offer';
    case NotInterested = 'not_interested';
    case DealLost = 'deal_lost';

    public function label(): string
    {
        return match ($this) {
            self::NotProcessed => 'Лид не обработан',
            self::NoAnswer => 'Не ответили на звонок',
            self::PreparingOffer => 'Составляем КП',
            self::NotInterested => 'Клиент неинтересен',
            self::DealLost => 'Сделка провалена',
        };
    }
}
