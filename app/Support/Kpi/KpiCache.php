<?php

namespace App\Support\Kpi;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Caches computed indicators. Every save or delete of an archive record or a setting
 * bumps a version number (see AppServiceProvider), so cached figures are never stale:
 * old entries are simply never read again and expire on their own.
 */
final class KpiCache
{
    private const VERSION_KEY = 'kpi:version';

    private static function store(): Repository
    {
        return Cache::store(config('cache.kpi_store'));
    }

    public static function remember(string $key, Closure $callback): mixed
    {
        $version = (int) self::store()->get(self::VERSION_KEY, 0);

        return self::store()->remember("kpi:{$version}:".md5($key), now()->addDay(), $callback);
    }

    public static function flush(): void
    {
        self::store()->forever(self::VERSION_KEY, (int) self::store()->get(self::VERSION_KEY, 0) + 1);
    }
}
