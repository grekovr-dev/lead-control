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
            self::NEW => 'Новый',
            self::CONTACTED => 'Связались',
            self::QUALIFIED => 'Квалифицирован',
            self::MEASURING_SCHEDULED => 'Замер запланирован',
            self::OFFER_PREPARED => 'Предложение подготовлено',
            self::WON => 'Выигран',
            self::LOST => 'Потерян',
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
