<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class ReportsIndexController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.reports.index');
    }
}
