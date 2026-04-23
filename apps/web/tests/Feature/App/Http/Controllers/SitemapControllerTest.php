<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use Tests\TestCase;

final class SitemapControllerTest extends TestCase
{
    public function test_it_renders_a_route_generated_xml_sitemap_for_the_landing_page(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee('<loc>'.route('landing').'</loc>', false);
        $response->assertSee('<loc>'.route('landing.geo', ['landingGeoSlug' => 'boryspil']).'</loc>', false);
        $response->assertSee('<changefreq>weekly</changefreq>', false);
        $response->assertSee('<priority>1.0</priority>', false);
    }
}
