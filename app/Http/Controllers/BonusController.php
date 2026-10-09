<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\PageSetting;
use App\Support\VisitorCountry;
use Illuminate\View\View;

class BonusController extends Controller
{
    public function index(): View
    {
        return view('bonuses.index', [
            'page' => PageSetting::for('bonuses'),
            'bonuses' => Bonus::forBonusesPage(VisitorCountry::code()),
            'canonical' => rtrim(localized_url(null, 'bonuses'), '/').'/',
        ]);
    }
}
