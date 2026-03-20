<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Domain\Lead;

use Inbound\Domain\Lead\LeadStatus;
use PHPUnit\Framework\TestCase;

final class LeadStatusTest extends TestCase
{
    public function test_it_exposes_expected_string_values(): void
    {
        $this->assertSame('new', LeadStatus::NEW->value);
        $this->assertSame('contacted', LeadStatus::CONTACTED->value);
        $this->assertSame('qualified', LeadStatus::QUALIFIED->value);
        $this->assertSame('measuring_scheduled', LeadStatus::MEASURING_SCHEDULED->value);
        $this->assertSame('offer_prepared', LeadStatus::OFFER_PREPARED->value);
        $this->assertSame('won', LeadStatus::WON->value);
        $this->assertSame('lost', LeadStatus::LOST->value);
    }

    public function test_it_returns_labels_for_all_statuses(): void
    {
        $this->assertSame('Новый', LeadStatus::NEW->label());
        $this->assertSame('Связались', LeadStatus::CONTACTED->label());
        $this->assertSame('Квалифицирован', LeadStatus::QUALIFIED->label());
        $this->assertSame('Замер запланирован', LeadStatus::MEASURING_SCHEDULED->label());
        $this->assertSame('Предложение подготовлено', LeadStatus::OFFER_PREPARED->label());
        $this->assertSame('Выигран', LeadStatus::WON->label());
        $this->assertSame('Потерян', LeadStatus::LOST->label());
    }

    public function test_it_returns_options_map(): void
    {
        $this->assertSame([
            'new' => 'Новый',
            'contacted' => 'Связались',
            'qualified' => 'Квалифицирован',
            'measuring_scheduled' => 'Замер запланирован',
            'offer_prepared' => 'Предложение подготовлено',
            'won' => 'Выигран',
            'lost' => 'Потерян',
        ], LeadStatus::options());
    }
}
