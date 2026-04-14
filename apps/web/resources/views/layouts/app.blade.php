<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Натяжні стелі в Києві та області під ключ | Добрі стелі</title>
    <meta name="description" content="Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Києві та області. Працюємо швидко, акуратно та під ключ.">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ route('landing') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:locale" content="uk_UA">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Добрі стелі">
    <meta property="og:title" content="Натяжні стелі в Києві та області під ключ | Добрі стелі">
    <meta property="og:description" content="Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Києві та області. Працюємо швидко, акуратно та під ключ.">
    <meta property="og:url" content="{{ route('landing') }}">
    <meta property="og:image" content="{{ asset('images/hero-cropped.jpg') }}">
    <meta property="og:image:alt" content="Натяжні стелі в Києві та області">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Натяжні стелі в Києві та області під ключ | Добрі стелі">
    <meta name="twitter:description" content="Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Києві та області. Працюємо швидко, акуратно та під ключ.">
    <meta name="twitter:image" content="{{ asset('images/hero-cropped.jpg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $landingServiceSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => 'Натяжні стелі в Києві та області',
            'description' => 'Монтаж натяжних стель у Києві та області з безкоштовним заміром і попереднім прорахунком вартості.',
            'url' => route('landing'),
            'areaServed' => [
                [
                    '@type' => 'City',
                    'name' => 'Київ',
                ],
                [
                    '@type' => 'AdministrativeArea',
                    'name' => 'Київська область',
                ],
            ],
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
    @yield('content')
</body>
</html>
