<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use Tests\TestCase;

final class RobotsControllerTest extends TestCase
{
    public function test_it_renders_a_route_generated_robots_file_with_the_sitemap_url(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow:', false);
        $response->assertSee('Sitemap: '.route('sitemap'), false);
    }
}
