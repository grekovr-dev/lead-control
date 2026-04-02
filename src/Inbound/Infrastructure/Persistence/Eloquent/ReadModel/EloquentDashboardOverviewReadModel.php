<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardBreakdownItemView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardRecentLeadView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewQuery;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use UnexpectedValueException;

final class EloquentDashboardOverviewReadModel implements DashboardOverviewReadModel
{
    public function __invoke(GetDashboardOverviewQuery $query): DashboardOverviewView
    {
        unset($query);

        $clicksCount = ClickModel::query()->count();
        $visitsCount = VisitModel::query()->count();
        $touchesCount = TouchModel::query()->count();
        $leadsCount = LeadModel::query()->count();

        return new DashboardOverviewView(
            clicksCount: $clicksCount,
            visitsCount: $visitsCount,
            touchesCount: $touchesCount,
            leadsCount: $leadsCount,
            clicksToLeadsConversionRate: $this->calculateConversionRate($clicksCount, $leadsCount),
            visitsToLeadsConversionRate: $this->calculateConversionRate($visitsCount, $leadsCount),
            leadStatusBreakdown: $this->buildLeadStatusBreakdown(),
            touchTypeBreakdown: $this->buildTouchTypeBreakdown(),
            leadOriginBreakdown: $this->buildLeadOriginBreakdown(),
            recentLeads: $this->buildRecentLeads(),
        );
    }

    /**
     * @return list<DashboardBreakdownItemView>
     */
    private function buildLeadStatusBreakdown(): array
    {
        /** @var array<string, int|string> $counts */
        $counts = LeadModel::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $items = [];

        foreach (LeadStatus::cases() as $status) {
            $items[] = new DashboardBreakdownItemView(
                key: $status->value,
                label: $status->label(),
                count: (int) ($counts[$status->value] ?? 0),
            );
        }

        return $items;
    }

    /**
     * @return list<DashboardBreakdownItemView>
     */
    private function buildTouchTypeBreakdown(): array
    {
        /** @var array<string, int|string> $counts */
        $counts = TouchModel::query()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $items = [];

        foreach (TouchType::cases() as $type) {
            $items[] = new DashboardBreakdownItemView(
                key: $type->value,
                label: $this->touchTypeLabel($type),
                count: (int) ($counts[$type->value] ?? 0),
            );
        }

        return $items;
    }

    /**
     * @return list<DashboardBreakdownItemView>
     */
    private function buildLeadOriginBreakdown(): array
    {
        /** @var array<string, int|string> $counts */
        $counts = LeadModel::query()
            ->selectRaw('origin, COUNT(*) as aggregate')
            ->groupBy('origin')
            ->orderBy('origin')
            ->pluck('aggregate', 'origin')
            ->all();

        $items = [];

        foreach ($counts as $origin => $count) {
            $items[] = new DashboardBreakdownItemView(
                key: $origin,
                label: $this->leadOriginLabel($origin),
                count: (int) $count,
            );
        }

        return $items;
    }

    /**
     * @return list<DashboardRecentLeadView>
     */
    private function buildRecentLeads(): array
    {
        /** @var list<LeadModel> $models */
        $models = LeadModel::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->all();

        $items = [];

        foreach ($models as $model) {
            $status = $model->getAttribute('status');
            $leadStatus = $status instanceof LeadStatus
                ? $status
                : LeadStatus::from((string) $status);

            $items[] = new DashboardRecentLeadView(
                leadId: (string) $model->getAttribute('id'),
                shortLeadId: $this->shortLeadId((string) $model->getAttribute('id')),
                visitorId: $this->nullableString($model->getAttribute('visitor_id')),
                visitId: $this->nullableString($model->getAttribute('visit_id')),
                name: $this->nullableString($model->getAttribute('name')),
                phone: $this->nullableString($model->getAttribute('phone')),
                status: $leadStatus->value,
                statusLabel: $leadStatus->label(),
                origin: (string) $model->getAttribute('origin'),
                originLabel: $this->leadOriginLabel((string) $model->getAttribute('origin')),
                attributionSource: $this->nullableString($model->getAttribute('visit_attribution_source')),
                attributionMedium: $this->nullableString($model->getAttribute('visit_attribution_medium')),
                createdAt: $this->toDateTimeImmutable($model->getAttribute('created_at')),
            );
        }

        return $items;
    }

    private function touchTypeLabel(TouchType $type): string
    {
        return match ($type) {
            TouchType::PhoneClick => 'Клік по телефону',
            TouchType::LeadFormClick => 'Клік по формі',
            TouchType::MessengerClick => 'Клік по месенджеру',
            TouchType::WorksClick => 'Клік по роботах',
        };
    }

    private function leadOriginLabel(string $origin): string
    {
        return match ($origin) {
            'form' => 'Форма',
            'phone_click' => 'Клік по телефону',
            'messenger_click' => 'Клік по месенджеру',
            default => $origin,
        };
    }

    private function shortLeadId(string $leadId): string
    {
        $segments = explode('-', $leadId);

        if (count($segments) < 2) {
            return $leadId;
        }

        return implode('-', array_slice($segments, 0, 2));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function calculateConversionRate(int $fromCount, int $toCount): float
    {
        if ($fromCount <= 0) {
            return 0.0;
        }

        return round(($toCount / $fromCount) * 100, 2);
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from LeadModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
