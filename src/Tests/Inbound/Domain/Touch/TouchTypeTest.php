<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Touch;

use Inbound\Domain\Touch\TouchType;
use PHPUnit\Framework\TestCase;

final class TouchTypeTest extends TestCase
{
    public function test_it_exposes_expected_string_values(): void
    {
        $this->assertSame('phone_click', TouchType::PhoneClick->value);
        $this->assertSame('form_submit', TouchType::FormSubmit->value);
        $this->assertSame('messenger_click', TouchType::MessengerClick->value);
        $this->assertSame('cta_click', TouchType::CtaClick->value);
    }
}
