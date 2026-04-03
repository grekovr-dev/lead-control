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
            'Точка входу в аналітичний розділ бекофісу з готовими звітами та переходами до детальних списків.',
            'Розділ звітів',
            'Стан розділу',
            '5 звітів',
            'Доступні звіти',
            'Статуси лідів',
            'Воронка за походженням',
            'Воронка атрибуції візитів',
            'Воронка залучення відвідувачів',
            'Динаміка воронки',
        ]);
        $response->assertSee('<html lang="uk">', false);
        $response->assertSee('<title>Звіти • Lead Control</title>', false);
        $response->assertSee('title="Огляд"', false);
        $response->assertSee('href="'.route('admin.dashboard').'"', false);
        $response->assertSee('title="Ліди"', false);
        $response->assertSee('href="'.route('admin.leads.index').'"', false);
        $response->assertSee('title="Звіти"', false);
        $response->assertSee('href="'.route('admin.reports.index').'"', false);
    }

    public function test_it_renders_links_for_available_reporting_slices(): void
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('href="'.route('admin.reports.lead-status').'"', false);
        $response->assertSee('href="'.route('admin.reports.origin-funnel').'"', false);
        $response->assertSee('href="'.route('admin.reports.visit-attribution-funnel').'"', false);
        $response->assertSee('href="'.route('admin.reports.visitor-acquisition-funnel').'"', false);
        $response->assertSee('href="'.route('admin.reports.funnel-trends').'"', false);
        $response->assertDontSeeText('Готово');
        $response->assertSeeText([
            'Розподіл поточного стану лідів без переходу в історію змін.',
            'Зріз по origin з мапованими дотиками, лідами та переходом у список дотиків.',
            'Візитний зріз: сирі кліки як контекст, візити й ліди як основа конверсії в межах вибраного періоду.',
            'Когортний зріз за першим візитом: які джерела вперше приводять людей, що згодом стають лідами, навіть якщо лід створено пізніше.',
            'Денний зріз кліків, візитів і лідів із акцентом на співвідношення кліків до лідів та конверсію візитів у ліди.',
        ]);
    }
}
