<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Inbound\Application\Transactions\TransactionManager;

final class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
