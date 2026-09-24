<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Support\Facades\Lang;
use RuntimeException;

final class LandingContentResolver
{
    /** @return array<string, mixed> */
    public function resolve(string $locale, ?string $areaSlug = null, ?string $complexSlug = null): array
    {
        $base = $this->load('landing/capture', $locale);
        $area = $areaSlug === null ? null : $this->load("landing/pages/{$areaSlug}", $locale);
        $complex = null;

        if ($complexSlug !== null) {
            if ($areaSlug === null) {
                throw new RuntimeException('A complex landing requires an area.');
            }

            $complex = $this->load("landing/pages/{$areaSlug}/{$complexSlug}", $locale);
        }

        return $this->compose($base, $area, $complex);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  ?array<string, mixed>  $area
     * @param  ?array<string, mixed>  $complex
     * @return array<string, mixed>
     */
    public function compose(array $base, ?array $area = null, ?array $complex = null): array
    {
        $copy = $base;
        $copy['geo'] = $copy['geo']['default'];

        if ($area !== null) {
            $copy = $this->apply($copy, $area);
        }

        if ($complex !== null) {
            $copy = $this->apply($copy, $complex);
        }

        $copy['faq']['items'] = array_merge(
            $copy['faq']['items'],
            $copy['faq']['local_items'] ?? [],
        );
        unset($copy['faq']['local_items']);

        return $copy;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function apply(array $base, array $overrides): array
    {
        $localItems = array_merge(
            $base['faq']['local_items'] ?? [],
            $overrides['faq']['local_items'] ?? [],
        );
        $merged = $this->merge($base, $overrides);

        if ($localItems !== []) {
            $merged['faq']['local_items'] = $localItems;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function merge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)
                && ! array_is_list($base[$key]) && ! array_is_list($value)) {
                $base[$key] = $this->merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /** @return array<string, mixed> */
    private function load(string $key, string $locale): array
    {
        if (! Lang::has($key, $locale, false)) {
            throw new RuntimeException("Landing content is missing: {$key} ({$locale}).");
        }

        $copy = Lang::get($key, [], $locale, false);

        if (! is_array($copy)) {
            throw new RuntimeException("Landing content is not an array: {$key} ({$locale}).");
        }

        return $copy;
    }
}
