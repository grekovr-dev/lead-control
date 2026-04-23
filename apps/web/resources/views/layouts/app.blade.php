<!DOCTYPE html>
<html lang="uk">
<head>
    @php
        $googleTagManagerId = config('services.google_tag_manager.id');
        $landingGeo = $landingGeo ?? null;

        $landingTitle = $landingGeo?->title ?? 'Натяжні стелі в Києві та області під ключ | Добрі стелі';
        $landingDescription = $landingGeo?->description ?? 'Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Києві та області. Працюємо швидко та якісно.';
        $landingCanonicalUrl = $landingGeo?->canonicalUrl ?? route('landing');
        $landingOgImageAlt = $landingGeo?->ogImageAlt ?? 'Натяжні стелі в Києві та області';
        $landingSchemaName = $landingGeo?->schemaName ?? 'Натяжні стелі в Києві та області';
        $landingSchemaDescription = $landingGeo?->schemaDescription ?? 'Монтаж натяжних стель у Києві та області з безкоштовним заміром і попереднім прорахунком вартості.';
        $landingAreaServed = $landingGeo?->areaServed ?? [
            'Київ',
            'Київська область',
        ];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingTitle }}</title>
    <meta name="description" content="{{ $landingDescription }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $landingCanonicalUrl }}">
    @if (is_string($googleTagManagerId) && $googleTagManagerId !== '' && $googleTagManagerId !== 'CHANGE_ME')
    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l !== 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '{{ $googleTagManagerId }}');
    </script>
    <!-- End Google Tag Manager -->
    @endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:locale" content="uk_UA">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Добрі стелі">
    <meta property="og:title" content="{{ $landingTitle }}">
    <meta property="og:description" content="{{ $landingDescription }}">
    <meta property="og:url" content="{{ $landingCanonicalUrl }}">
    <meta property="og:image" content="{{ asset('images/hero-cropped.jpg') }}">
    <meta property="og:image:alt" content="{{ $landingOgImageAlt }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingTitle }}">
    <meta name="twitter:description" content="{{ $landingDescription }}">
    <meta name="twitter:image" content="{{ asset('images/hero-cropped.jpg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $landingServiceSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $landingSchemaName,
            'description' => $landingSchemaDescription,
            'url' => $landingCanonicalUrl,
            'areaServed' => array_map(
                fn (string $area): array => [
                    '@type' => str_contains($area, 'область') ? 'AdministrativeArea' : 'City',
                    'name' => $area,
                ],
                $landingAreaServed,
            ),
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Добрі стелі',
            ],
        ];
    @endphp
    <script type="application/ld+json">
        @json($landingServiceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @if (is_string($googleTagManagerId) && $googleTagManagerId !== '' && $googleTagManagerId !== 'CHANGE_ME')
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $googleTagManagerId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @endif
    @yield('content')
</body>
</html>
