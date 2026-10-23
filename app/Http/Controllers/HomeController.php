<?php

namespace App\Http\Controllers;

use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\Faq;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'stats' => [
                'centres' => ExamCentre::query()->active()->count(),
                'exams' => Examination::query()->active()->count(),
                'cities' => ExamCentre::query()->active()->distinct()->count('city'),
            ],
            'examinations' => Examination::query()->active()
                ->withCount(['centres' => fn ($q) => $q->where('status', 'active')])
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'recentCentres' => ExamCentre::query()->active()
                ->with(['examination:id,name'])
                ->latest('id')
                ->limit(6)
                ->get(),
            'topFaqs' => Faq::query()->published()->orderBy('sort_order')->limit(4)->get(),
            'demoDataPresent' => ExamCentre::query()->where('is_demo', true)->exists(),
        ]);
    }
}
