@extends('layouts.app')
@section('title', __('messages.legal.terms_title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.legal.terms_title') }}</h1>
    <p class="muted">Plain-language terms for using Exam Travel Assist.</p>
</div>

<section class="section">
    <h2>What this platform is</h2>
    <p>{{ config('exam_travel.brand.disclaimer') }}</p>
    <p>The platform helps you plan travel to an examination centre. It does not allot centres, does not conduct examinations, and does not sell tickets.</p>

    <h2>Information accuracy</h2>
    <ul>
        <li>Every centre and listing shows its source and how it was verified. Information that has not been verified is labelled as such.</li>
        <li>Distances and prices labelled <span class="badge b-estimated">estimated</span> are disclosed planning ranges with the calculation method shown — they are not live fares or guaranteed availability.</li>
        <li>Always confirm your centre, code and address from your official admit card, and confirm fares, timings and availability on the official booking site.</li>
    </ul>

    <h2>Your responsibilities</h2>
    <ul>
        <li>Provide accurate registration details and keep your password secure.</li>
        <li>Do not post unlawful, abusive, misleading or private content in the community.</li>
        <li>Do not scrape, overload or attempt to circumvent the platform's rate limits or security controls.</li>
    </ul>

    <h2>Availability and liability</h2>
    <p>External integrations may be unavailable or rate-limited. Where a provider is unavailable, the platform shows an honest empty state rather than substituting invented data. To the extent permitted by law, the platform is provided "as is" without warranty, and you remain responsible for your travel decisions.</p>
</section>

<div class="panel">
    <p class="mb-0">Questions: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
</div>
@endsection
