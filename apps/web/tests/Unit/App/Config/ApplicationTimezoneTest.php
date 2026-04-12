<?php

declare(strict_types=1);

namespace Tests\Unit\App\Config;

use Tests\TestCase;

final class ApplicationTimezoneTest extends TestCase
{
    public function test_it_defaults_the_application_and_php_timezone_to_europe_kyiv(): void
    {
        $this->assertSame('Europe/Kyiv', config('app.timezone'));
        $this->assertSame('Europe/Kyiv', date_default_timezone_get());
    }
}
