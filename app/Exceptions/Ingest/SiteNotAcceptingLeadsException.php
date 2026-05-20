<?php

namespace App\Exceptions\Ingest;

use App\Models\Site;
use RuntimeException;

class SiteNotAcceptingLeadsException extends RuntimeException
{
    public function __construct(public readonly Site $site)
    {
        parent::__construct('Site is not accepting leads.');
    }
}
