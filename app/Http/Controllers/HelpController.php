<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class HelpController extends Controller
{
    public function index()
    {
        return view('help.index', [
            'faqs' => Faq::query()->published()
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category'),
            'retentionDays' => (int) config('exam_travel.retention.chat_days', 90),
            'supportEmail' => config('exam_travel.brand.support_email'),
        ]);
    }

    public function privacy()
    {
        return view('legal.privacy', [
            'retention' => config('exam_travel.retention'),
            'supportEmail' => config('exam_travel.brand.support_email'),
        ]);
    }

    public function terms()
    {
        return view('legal.terms', ['supportEmail' => config('exam_travel.brand.support_email')]);
    }

    public function communityRules()
    {
        return view('legal.community-rules', ['supportEmail' => config('exam_travel.brand.support_email')]);
    }
}
