<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormCommand;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickAction;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickCommand;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Visit\Visit;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\RevisitModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;

final class VisitorAcquisitionDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const MAIN_DEMO_PERIOD_PRESET = 'previous_month';

    private const DEMO_VISITOR_PREFIX = 'demo-visitor-';

    public function run(): void
    {
        $telegramNotificationsEnabled = (bool) config('services.telegram.notifications_enabled', true);

        try {
            config()->set('services.telegram.notifications_enabled', false);

            $this->resetDemoData();

            $timeline = $this->timelineAnchors();

            $this->seedScenarioAHappyPathFirstTouchConversion($timeline);
            $this->seedScenarioBDirectRevisitWithinActiveSession($timeline);
            $this->seedScenarioCLongGapNewVisitAfterSessionExpiry($timeline);
            $this->seedScenarioDStrongAcquisitionBucket($timeline);
            $this->seedScenarioEWeakAcquisitionBucket($timeline);
            $this->seedScenarioFDirectAndUnknownAttributionBuckets($timeline);
            $this->seedScenarioGOutOfCohortLeadOnNewVisit($timeline);
        } finally {
            config()->set('services.telegram.notifications_enabled', $telegramNotificationsEnabled);
        }
    }

    private function registerClick(
        string $visitorId,
        Attribution $attribution,
        string $landingUrl,
        DateTimeImmutable $occurredAt,
    ): Visit {
        $action = app(RegisterClickAction::class);

        return $action(new RegisterClickCommand(
            visitorId: new VisitorId($visitorId),
            attribution: $attribution,
            landingUrl: $landingUrl,
            occurredAt: $occurredAt,
        ));
    }

    private function createFormLead(
        string $visitorId,
        ?string $name,
        string $phone,
        DateTimeImmutable $occurredAt,
    ): Lead {
        $action = app(CreateLeadFromFormAction::class);

        return $action(new CreateLeadFromFormCommand(
            visitorId: new VisitorId($visitorId),
            name: $name,
            phone: $phone,
            occurredAt: $occurredAt,
        ));
    }

    private function capturePhoneLead(
        string $visitorId,
        DateTimeImmutable $occurredAt,
    ): Lead|Touch {
        $action = app(CapturePhoneClickAction::class);

        return $action(new CapturePhoneClickCommand(
            visitorId: new VisitorId($visitorId),
            occurredAt: $occurredAt,
        ));
    }

    private function attribution(
        ?string $source = null,
        ?string $medium = null,
        ?string $campaign = null,
        ?string $content = null,
        ?string $term = null,
        ?string $gclid = null,
        ?string $fbclid = null,
        ?string $msclkid = null,
        ?string $referrer = null,
    ): Attribution {
        return new Attribution(
            source: $source,
            medium: $medium,
            campaign: $campaign,
            content: $content,
            term: $term,
            gclid: $gclid,
            fbclid: $fbclid,
            msclkid: $msclkid,
            referrer: $referrer,
        );
    }

    private function minutesAfter(DateTimeImmutable $base, int $minutes): DateTimeImmutable
    {
        return $base->modify(sprintf('+%d minutes', $minutes));
    }

    private function hoursAfter(DateTimeImmutable $base, int $hours): DateTimeImmutable
    {
        return $base->modify(sprintf('+%d hours', $hours));
    }

    private function daysAfter(DateTimeImmutable $base, int $days): DateTimeImmutable
    {
        return $base->modify(sprintf('+%d days', $days));
    }

    /**
     * @return array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * }
     */
    private function timelineAnchors(): array
    {
        $currentMonth = $this->startOfCurrentMonth();

        return [
            'twoMonthsAgo' => $currentMonth->modify('-2 months'),
            'previousMonth' => $currentMonth->modify('-1 month'),
            'currentMonth' => $currentMonth,
            'mainDemoPeriodPreset' => self::MAIN_DEMO_PERIOD_PRESET,
        ];
    }

    private function startOfCurrentMonth(): DateTimeImmutable
    {
        return new DateTimeImmutable('first day of this month 09:00:00');
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioAHappyPathFirstTouchConversion(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $firstClickAt = $this->daysAfter($timeline['previousMonth'], 2);
        $leadCreatedAt = $this->minutesAfter($firstClickAt, 15);
        $visitorId = 'demo-visitor-a';

        $this->registerClick(
            visitorId: $visitorId,
            attribution: $this->attribution(
                source: 'google',
                medium: 'cpc',
                campaign: 'spring-sale',
                referrer: 'https://googleads.g.doubleclick.net/',
            ),
            landingUrl: 'https://demo.lead-control.test/spring-offer',
            occurredAt: $firstClickAt,
        );

        $this->createFormLead(
            visitorId: $visitorId,
            name: 'Demo Happy Path',
            phone: '+380500000001',
            occurredAt: $leadCreatedAt,
        );
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioBDirectRevisitWithinActiveSession(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $firstClickAt = $this->daysAfter($timeline['previousMonth'], 5);
        $reloadClickAt = $this->minutesAfter($firstClickAt, 8);
        $leadCreatedAt = $this->minutesAfter($reloadClickAt, 12);
        $visitorId = 'demo-visitor-b';

        $firstVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: $this->attribution(
                source: 'facebook',
                medium: 'paid-social',
                campaign: 'lookalike',
                referrer: 'https://www.facebook.com/',
            ),
            landingUrl: 'https://demo.lead-control.test/lookalike-offer',
            occurredAt: $firstClickAt,
        );

        $continuedVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: Attribution::direct(),
            landingUrl: 'https://demo.lead-control.test/lookalike-offer',
            occurredAt: $reloadClickAt,
        );

        $this->assertSameVisit(
            expectedVisitId: $firstVisit->id()->value(),
            actualVisit: $continuedVisit,
            scenarioName: 'Scenario B direct revisit within active session',
        );

        $this->createFormLead(
            visitorId: $visitorId,
            name: 'Demo Direct Revisit',
            phone: '+380500000002',
            occurredAt: $leadCreatedAt,
        );
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioCLongGapNewVisitAfterSessionExpiry(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $firstTouchAttribution = $this->attribution(
            source: 'google',
            medium: 'cpc',
            campaign: 'spring-retarget',
            referrer: 'https://googleads.g.doubleclick.net/',
        );
        $firstClickAt = $this->daysAfter($timeline['previousMonth'], 7);
        $secondClickAt = $this->daysAfter($firstClickAt, 2);
        $leadCreatedAt = $this->minutesAfter($secondClickAt, 10);
        $visitorId = 'demo-visitor-c';

        $firstVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: $firstTouchAttribution,
            landingUrl: 'https://demo.lead-control.test/retarget-offer',
            occurredAt: $firstClickAt,
        );

        $secondVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: $this->attribution(
                source: 'google',
                medium: 'organic',
                campaign: 'retarget-return',
                referrer: 'https://www.google.com/',
            ),
            landingUrl: 'https://demo.lead-control.test/retarget-offer',
            occurredAt: $secondClickAt,
        );

        $this->assertSameVisit(
            expectedVisitId: $secondVisit->id()->value(),
            actualVisit: $secondVisit,
            scenarioName: 'Scenario C long gap new visit after session expiry',
        );

        if ($firstVisit->id()->value() === $secondVisit->id()->value()) {
            throw new \RuntimeException('Scenario C expected a new visit after session expiry, but the original visit was reused.');
        }

        $lead = $this->createFormLead(
            visitorId: $visitorId,
            name: 'Demo Long Gap Return',
            phone: '+380500000003',
            occurredAt: $leadCreatedAt,
        );

        $this->assertLeadAttributionSplit(
            lead: $lead,
            expectedVisitId: $secondVisit->id()->value(),
            expectedVisitAttribution: $secondVisit->firstAttribution(),
            expectedVisitorAttribution: $firstTouchAttribution,
            scenarioName: 'Scenario C long gap new visit after session expiry',
        );
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioDStrongAcquisitionBucket(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $bucketAttribution = $this->attribution(
            source: 'instagram',
            medium: 'paid-social',
            campaign: 'creator-bundle',
            referrer: 'https://www.instagram.com/',
        );

        $visitors = [
            [
                'visitorId' => 'demo-visitor-d1',
                'origin' => 'form',
                'name' => 'Demo Strong Bucket 1',
                'phone' => '+380500000004',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 10),
                'leadDelayMinutes' => 12,
            ],
            [
                'visitorId' => 'demo-visitor-d2',
                'origin' => 'form',
                'name' => 'Demo Strong Bucket 2',
                'phone' => '+380500000005',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 11),
                'leadDelayMinutes' => 18,
            ],
            [
                'visitorId' => 'demo-visitor-d3',
                'origin' => 'phone_click',
                'name' => 'Demo Strong Bucket 3',
                'phone' => '+380500000006',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 12),
                'leadDelayMinutes' => 25,
            ],
        ];

        foreach ($visitors as $visitor) {
            $this->registerClick(
                visitorId: $visitor['visitorId'],
                attribution: $bucketAttribution,
                landingUrl: 'https://demo.lead-control.test/creator-offer',
                occurredAt: $visitor['clickedAt'],
            );

            $leadOccurredAt = $this->minutesAfter($visitor['clickedAt'], $visitor['leadDelayMinutes']);

            if ($visitor['origin'] === 'phone_click') {
                $this->capturePhoneLead(
                    visitorId: $visitor['visitorId'],
                    occurredAt: $leadOccurredAt,
                );

                continue;
            }

            $this->createFormLead(
                visitorId: $visitor['visitorId'],
                name: $visitor['name'],
                phone: $visitor['phone'],
                occurredAt: $leadOccurredAt,
            );
        }
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioEWeakAcquisitionBucket(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $bucketAttribution = $this->attribution(
            source: 'google',
            medium: 'organic',
            campaign: null,
            referrer: 'https://www.google.com/',
        );

        $visitors = [
            [
                'visitorId' => 'demo-visitor-e1',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 14),
            ],
            [
                'visitorId' => 'demo-visitor-e2',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 16),
            ],
            [
                'visitorId' => 'demo-visitor-e3',
                'clickedAt' => $this->daysAfter($timeline['previousMonth'], 18),
            ],
        ];

        foreach ($visitors as $visitor) {
            $this->registerClick(
                visitorId: $visitor['visitorId'],
                attribution: $bucketAttribution,
                landingUrl: 'https://demo.lead-control.test/organic-guide',
                occurredAt: $visitor['clickedAt'],
            );
        }
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioFDirectAndUnknownAttributionBuckets(array $timeline): void
    {
        unset($timeline['twoMonthsAgo'], $timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $directFirstClickAt = $this->daysAfter($timeline['previousMonth'], 20);
        $directLeadCreatedAt = $this->minutesAfter($directFirstClickAt, 20);

        $this->registerClick(
            visitorId: 'demo-visitor-f1',
            attribution: Attribution::direct(),
            landingUrl: 'https://demo.lead-control.test/direct-offer',
            occurredAt: $directFirstClickAt,
        );

        $this->capturePhoneLead(
            visitorId: 'demo-visitor-f1',
            occurredAt: $directLeadCreatedAt,
        );

        $unknownFirstClickAt = $this->daysAfter($timeline['previousMonth'], 22);
        $unknownLeadCreatedAt = $this->minutesAfter($unknownFirstClickAt, 16);

        $this->registerClick(
            visitorId: 'demo-visitor-f2',
            attribution: $this->attribution(
                source: 'unknown',
                medium: 'email',
                campaign: 'partial-utm',
            ),
            landingUrl: 'https://demo.lead-control.test/partial-utm-offer',
            occurredAt: $unknownFirstClickAt,
        );

        $this->createFormLead(
            visitorId: 'demo-visitor-f2',
            name: 'Demo Unknown Source',
            phone: '+380500000008',
            occurredAt: $unknownLeadCreatedAt,
        );
    }

    /**
     * @param array{
     *     twoMonthsAgo: DateTimeImmutable,
     *     previousMonth: DateTimeImmutable,
     *     currentMonth: DateTimeImmutable,
     *     mainDemoPeriodPreset: string
     * } $timeline
     */
    private function seedScenarioGOutOfCohortLeadOnNewVisit(array $timeline): void
    {
        unset($timeline['currentMonth'], $timeline['mainDemoPeriodPreset']);

        $firstTouchAttribution = $this->attribution(
            source: 'microsoft',
            medium: 'cpc',
            campaign: 'archived-cohort',
            referrer: 'https://www.bing.com/',
        );
        $firstClickAt = $this->daysAfter($timeline['twoMonthsAgo'], 6);
        $secondClickAt = $this->daysAfter($timeline['previousMonth'], 24);
        $leadCreatedAt = $this->minutesAfter($secondClickAt, 14);
        $visitorId = 'demo-visitor-g1';

        $firstVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: $firstTouchAttribution,
            landingUrl: 'https://demo.lead-control.test/archived-offer',
            occurredAt: $firstClickAt,
        );

        $secondVisit = $this->registerClick(
            visitorId: $visitorId,
            attribution: $this->attribution(
                source: 'google',
                medium: 'organic',
                campaign: 'late-return',
                referrer: 'https://www.google.com/',
            ),
            landingUrl: 'https://demo.lead-control.test/archived-offer',
            occurredAt: $secondClickAt,
        );

        $this->assertSameVisit(
            expectedVisitId: $secondVisit->id()->value(),
            actualVisit: $secondVisit,
            scenarioName: 'Scenario G out-of-cohort lead on new visit',
        );

        if ($firstVisit->id()->value() === $secondVisit->id()->value()) {
            throw new \RuntimeException('Scenario G expected a new visit after the long gap, but the original visit was reused.');
        }

        $lead = $this->createFormLead(
            visitorId: $visitorId,
            name: 'Demo Out Of Cohort',
            phone: '+380500000009',
            occurredAt: $leadCreatedAt,
        );

        $this->assertLeadAttributionSplit(
            lead: $lead,
            expectedVisitId: $secondVisit->id()->value(),
            expectedVisitAttribution: $secondVisit->firstAttribution(),
            expectedVisitorAttribution: $firstTouchAttribution,
            scenarioName: 'Scenario G out-of-cohort lead on new visit',
        );
    }

    private function assertSameVisit(string $expectedVisitId, Visit $actualVisit, string $scenarioName): void
    {
        if ($actualVisit->id()->value() === $expectedVisitId) {
            return;
        }

        throw new \RuntimeException(sprintf(
            '%s expected visit %s, got %s.',
            $scenarioName,
            $expectedVisitId,
            $actualVisit->id()->value(),
        ));
    }

    private function assertLeadAttributionSplit(
        Lead $lead,
        string $expectedVisitId,
        Attribution $expectedVisitAttribution,
        Attribution $expectedVisitorAttribution,
        string $scenarioName,
    ): void {
        if ($lead->visitId()->value() !== $expectedVisitId) {
            throw new \RuntimeException(sprintf(
                '%s expected lead visit %s, got %s.',
                $scenarioName,
                $expectedVisitId,
                $lead->visitId()->value(),
            ));
        }

        if (! $lead->visitAttribution()->equals($expectedVisitAttribution)) {
            throw new \RuntimeException(sprintf(
                '%s expected lead visit attribution to follow the resolved visit.',
                $scenarioName,
            ));
        }

        if (! $lead->visitorAttribution()->equals($expectedVisitorAttribution)) {
            throw new \RuntimeException(sprintf(
                '%s expected lead visitor attribution to keep the first-touch acquisition values.',
                $scenarioName,
            ));
        }
    }

    private function resetDemoData(): void
    {
        TouchModel::query()
            ->where('visitor_id', 'like', self::DEMO_VISITOR_PREFIX.'%')
            ->delete();

        ClickModel::query()
            ->where('visitor_id', 'like', self::DEMO_VISITOR_PREFIX.'%')
            ->delete();

        LeadModel::query()
            ->where('visitor_id', 'like', self::DEMO_VISITOR_PREFIX.'%')
            ->delete();

        RevisitModel::query()
            ->where('visitor_id', 'like', self::DEMO_VISITOR_PREFIX.'%')
            ->delete();

        VisitModel::query()
            ->where('visitor_id', 'like', self::DEMO_VISITOR_PREFIX.'%')
            ->delete();
    }
}
