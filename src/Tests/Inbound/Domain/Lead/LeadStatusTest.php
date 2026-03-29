<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Lead;

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
        $this->assertSame('Новий', LeadStatus::NEW->label());
        $this->assertSame('Зв’язалися', LeadStatus::CONTACTED->label());
        $this->assertSame('Кваліфікований', LeadStatus::QUALIFIED->label());
        $this->assertSame('Замір заплановано', LeadStatus::MEASURING_SCHEDULED->label());
        $this->assertSame('Пропозицію підготовлено', LeadStatus::OFFER_PREPARED->label());
        $this->assertSame('Успішний', LeadStatus::WON->label());
        $this->assertSame('Втрачений', LeadStatus::LOST->label());
    }

    public function test_it_returns_options_map(): void
    {
        $this->assertSame([
            'new' => 'Новий',
            'contacted' => 'Зв’язалися',
            'qualified' => 'Кваліфікований',
            'measuring_scheduled' => 'Замір заплановано',
            'offer_prepared' => 'Пропозицію підготовлено',
            'won' => 'Успішний',
            'lost' => 'Втрачений',
        ], LeadStatus::options());
    }
}
