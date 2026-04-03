<?php

declare(strict_types=1);

namespace Inbound\Application\Transactions;

interface TransactionManager
{
    public function run(callable $callback): mixed;
}
