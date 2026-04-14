<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response|View
    {
        return response()
            ->view('sitemap', [
                'landingUrl' => route('landing'),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
