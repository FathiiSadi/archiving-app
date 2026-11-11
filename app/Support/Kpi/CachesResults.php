<?php

namespace App\Support\Kpi;

use Closure;

trait CachesResults
{
    protected function cached(string $method, Closure $callback): mixed
    {
        return KpiCache::remember(static::class.'@'.$method.':'.json_encode($this->semesterIds), $callback);
    }
}
