<?php

namespace App\Http\Controllers;

use App\Models\CoverageArea;
use App\Models\Odp;
use App\Models\Village;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'coverageArea' => CoverageArea::query()->where('is_active', true)->first(),
            'villages' => Village::query()->orderBy('name')->get(),
            'odps' => Odp::query()->whereIn('status', ['active', 'planned'])->orderBy('code')->get(),
        ]);
    }
}
