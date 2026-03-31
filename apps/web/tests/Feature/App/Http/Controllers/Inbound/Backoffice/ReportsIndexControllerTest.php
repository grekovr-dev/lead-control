<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportsIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_reporting_entry_screen_inside_the_backoffice_shell(): void
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.index');
        $response->assertSeeText([
            'Lead Control',
            'Бекофіс',
            'Звіти',
            'Точка входу в аналітичний розділ бекофісу з майбутніми звітами та drill-down екранами.',
            'Розділ звітів',
            'Стан розділу',
            'Підготовка',
            'Заплановані звіти',
            'Статуси лідів',
            'Воронка за походженням',
            'Воронка за атрибуцією',
            'Динаміка воронки',
            'Незабаром',
        ]);
        $response->assertSee('<html lang="uk">', false);
        $response->assertSee('<title>Звіти • Lead Control</title>', false);
        $response->assertSee('title="Огляд"', false);
        $response->assertSee('href="' . route('admin.dashboard') . '"', false);
        $response->assertSee('title="Ліди"', false);
        $response->assertSee('href="' . route('admin.leads.index') . '"', false);
        $response->assertSee('title="Звіти"', false);
        $response->assertSee('href="' . route('admin.reports.index') . '"', false);
    }

    public function test_it_renders_placeholder_links_for_future_reporting_slices(): void
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('href="' . route('admin.reports.lead-status') . '"', false);
        $response->assertDontSeeText('Готово');
        $response->assertSee('aria-disabled="true"', false);
        $response->assertSee('href="' . route('admin.reports.index') . '"', false);
        $response->assertSeeText([
            'Розподіл поточного стану лідів без переходу в історію змін.',
            'Зріз по origin з майбутнім drill-down у події.',
            'First-touch acquisition funnel на базі вже погодженої семантики.',
            'Тренди за періодами для основних етапів funnel.',
        ]);
    }
}
