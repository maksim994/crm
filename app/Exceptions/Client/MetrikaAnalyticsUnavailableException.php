<?php

namespace App\Exceptions\Client;

use Exception;

class MetrikaAnalyticsUnavailableException extends Exception
{
    public function __construct(string $message = 'Аналитика Метрики недоступна.')
    {
        parent::__construct($message);
    }
}
