<?php

namespace SMWks\LaravelZenith\Http\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

class ZenithPolicy
{
    public function manage(?Authenticatable $user): bool
    {
        $middleware = config('zenith.route.middleware', ['web', 'auth']);

        return collect($middleware)->contains(fn ($m) => str_starts_with($m, 'auth'));
    }
}
