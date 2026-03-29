<?php

declare(strict_types=1);

namespace Inbound\Domain\Lead;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case MEASURING_SCHEDULED = 'measuring_scheduled';
    case OFFER_PREPARED = 'offer_prepared';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новий',
            self::CONTACTED => 'Зв’язалися',
            self::QUALIFIED => 'Кваліфікований',
            self::MEASURING_SCHEDULED => 'Замір заплановано',
            self::OFFER_PREPARED => 'Пропозицію підготовлено',
            self::WON => 'Успішний',
            self::LOST => 'Втрачений',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
