<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class RobotsController extends Controller
{
    public function __invoke(): Response|View
    {
        return response()
            ->view('robots', [
                'sitemapUrl' => route('sitemap'),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
