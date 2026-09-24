<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\LandingContentResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LandingContentResolverTest extends TestCase
{
    #[Test]
    public function it_inherits_common_area_and_complex_content_with_explicit_list_rules(): void
    {
        $resolver = new LandingContentResolver;

        $copy = $resolver->compose(
            [
                'geo' => ['default' => ['h1' => 'General heading', 'description' => 'General description']],
                'hero' => ['consultation' => 'General consultation'],
                'faq' => ['items' => [['question' => 'General question', 'answer' => 'General answer']]],
            ],
            [
                'geo' => ['h1' => 'Area heading'],
                'faq' => ['local_items' => [['question' => 'Area question', 'answer' => 'Area answer']]],
            ],
            [
                'geo' => ['h1' => 'Complex heading'],
                'faq' => ['local_items' => [['question' => 'Complex question', 'answer' => 'Complex answer']]],
            ],
        );

        $this->assertSame('Complex heading', $copy['geo']['h1']);
        $this->assertSame('General description', $copy['geo']['description']);
        $this->assertSame('General consultation', $copy['hero']['consultation']);
        $this->assertSame(
            ['General question', 'Area question', 'Complex question'],
            array_column($copy['faq']['items'], 'question'),
        );
    }

    #[Test]
    public function it_loads_the_existing_boryspil_overrides_for_each_locale(): void
    {
        $resolver = new LandingContentResolver;

        $uk = $resolver->resolve('uk', 'boryspil');
        $ru = $resolver->resolve('ru', 'boryspil');

        $this->assertSame('Натяжні стелі в Борисполі', $uk['geo']['h1']);
        $this->assertSame('Натяжные потолки в Борисполе', $ru['geo']['h1']);
        $this->assertSame('Безкоштовний замір', $uk['benefits']['items'][0]['title']);
        $this->assertSame('Бесплатный замер', $ru['benefits']['items'][0]['title']);
    }

    #[Test]
    public function every_published_landing_has_uk_content(): void
    {
        $this->assertPublishedLandingContentExists('uk');
    }

    #[Test]
    public function every_published_landing_has_ru_content(): void
    {
        $this->assertPublishedLandingContentExists('ru');
    }

    private function assertPublishedLandingContentExists(string $locale): void
    {
        $resolver = new LandingContentResolver;
        $this->assertNotEmpty($resolver->resolve($locale)['geo']['h1']);

        foreach (config('landing_pages.areas') as $areaSlug => $area) {
            $this->assertNotEmpty($resolver->resolve($locale, $areaSlug)['geo']['h1']);

            foreach ($area['complexes'] as $complexSlug) {
                $this->assertNotEmpty($resolver->resolve($locale, $areaSlug, $complexSlug)['geo']['h1']);
            }
        }
    }
}
