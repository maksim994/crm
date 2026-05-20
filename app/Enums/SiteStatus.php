<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function acceptsLeads(): bool
    {
        return $this === self::Active;
    }
}
