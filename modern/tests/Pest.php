<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->extend(TestCase::class)->in('Unit');

/*
| Characterization tests load the legacy app's own PHP functions, so they need
| no Laravel at all.
*/

function agent(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function admin(array $attributes = []): User
{
    return User::factory()->admin()->create($attributes);
}

/**
 * Count the queries a callback runs. Used to guard against N+1 regressions.
 */
function countQueries(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}
