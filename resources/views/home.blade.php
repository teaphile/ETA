@extends('layouts.app')
@section('title', __('messages.home.title'))

@section('content')
<section class="hero">
    <h1>{{ __('messages.home.hero_title') }}</h1>
    <p>{{ __('messages.home.hero_body') }}</p>
    <p class="hero-stats">{{ __('messages.home.stats', [
        'centres' => number_format($stats['centres']),
        'exams' => number_format($stats['exams']),
        'cities' => number_format($stats['cities']),
    ]) }}</p>
    <div class="hero-actions">
        <a class="btn amber" href="{{ route('centres.index') }}">{{ __('messages.home.cta_search') }}</a>
        <a class="btn ghost" href="{{ route('journey.index') }}">{{ __('messages.home.cta_plan') }}</a>
    </div>
    <p class="hero-note">{{ __('messages.app.independent') }} · {{ __('messages.footer.verify') }}</p>
</section>

@if($demoDataPresent)
    <div class="alert warn">
        <p><span class="badge b-demo">{{ __('messages.common.demo') }}</span></p>
        <p class="mb-0">{{ __('messages.home.demo_notice') }}</p>
    </div>
@endif

<section class="section">
    <h2>{{ __('messages.home.how_it_works') }}</h2>
    <div class="steps">
        <span class="step">{{ __('messages.home.step1_title') }}</span>
        <span class="step">{{ __('messages.home.step2_title') }}</span>
        <span class="step">{{ __('messages.home.step3_title') }}</span>
        <span class="step">{{ __('messages.home.step4_title') }}</span>
    </div>
    <div class="grid">
        <article class="card">
            <h3>{{ __('messages.home.step1_title') }}</h3>
            <p class="muted">{{ __('messages.home.step1_body') }}</p>
            <div class="card-actions">
                <a class="btn sm" href="{{ route('centres.index') }}">{{ __('messages.nav.centres') }}</a>
            </div>
        </article>
        <article class="card">
            <h3>{{ __('messages.home.step2_title') }}</h3>
            <p class="muted">{{ __('messages.home.step2_body') }}</p>
            <div class="card-actions">
                <a class="btn sm" href="{{ route('journey.index') }}">{{ __('messages.nav.journey') }}</a>
            </div>
        </article>
        <article class="card">
            <h3>{{ __('messages.home.step3_title') }}</h3>
            <p class="muted">{{ __('messages.home.step3_body') }}</p>
            <div class="card-actions">
                <a class="btn sm" href="{{ route('places.index') }}">{{ __('messages.nav.places') }}</a>
            </div>
        </article>
        <article class="card">
            <h3>{{ __('messages.home.step4_title') }}</h3>
            <p class="muted">{{ __('messages.home.step4_body') }}</p>
            <div class="card-actions">
                <a class="btn sm" href="{{ route('assistant.index') }}">{{ __('messages.nav.assistant') }}</a>
            </div>
        </article>
    </div>
</section>

<section class="section">
    <h2>{{ __('messages.home.exams_covered') }}</h2>
    @if($examinations->isEmpty())
        <p class="muted">{{ __('messages.home.no_exams') }}</p>
    @else
        <div class="chiprow">
            @foreach($examinations as $exam)
                <a class="chip" href="{{ route('centres.index', ['exam' => $exam->id]) }}">
                    {{ $exam->name }}
                    <span class="badge b-neutral">{{ $exam->centres_count }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="section">
    <h2>{{ __('messages.home.recent_centres') }}</h2>
    @if($recentCentres->isEmpty())
        <div class="empty card">
            <span class="empty-icon" aria-hidden="true">🗺️</span>
            <h3>{{ __('messages.home.no_centres_title') }}</h3>
            <p>{{ __('messages.home.no_centres_body') }}</p>
            <a class="btn" href="{{ route('centres.index') }}">{{ __('messages.home.cta_search') }}</a>
        </div>
    @else
        <div class="grid">
            @foreach($recentCentres as $centre)
                @include('centres._card', ['centre' => $centre])
            @endforeach
        </div>
    @endif
</section>

@if($topFaqs->isNotEmpty())
    <section class="section">
        <h2>{{ __('messages.nav.help') }}</h2>
        <div class="grid-2">
            @foreach($topFaqs as $faq)
                <article class="card">
                    <h3>{{ $faq->question }}</h3>
                    <p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 220) }}</p>
                    @if($faq->source_url)
                        <div class="card-actions">
                            <a class="btn sm ghost" href="{{ $faq->source_url }}" target="_blank" rel="noopener nofollow">{{ __('messages.common.read_more') }} ↗</a>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <p class="mt-5 mb-0"><a class="btn ghost" href="{{ route('help.index') }}">{{ __('messages.nav.help') }}</a></p>
    </section>
@endif

<div class="panel panel-soft">
    <strong>{{ __('messages.app.independent') }}</strong>
    <p class="muted mb-0">{{ config('exam_travel.brand.disclaimer') }}</p>
</div>
@endsection
