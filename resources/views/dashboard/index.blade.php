@extends('layouts.app')
@section('title', __('messages.nav.dashboard'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.nav.dashboard') }}</h1>
    <p class="muted">Namaste {{ $user->name }}. Everything you save stays tied to your account and can be removed from your profile.</p>
</div>

@if($user->hasPendingDeletion())
    <div class="alert warn">
        {{ __('messages.profile.deletion_requested_banner') }}
        <a href="{{ route('profile') }}">Manage →</a>
    </div>
@endif

<div class="stat-grid">
    <div class="stat"><span class="stat-value">{{ $tripCount }}</span><span class="stat-label">{{ __('messages.nav.trips') }}</span></div>
    <div class="stat"><span class="stat-value">{{ $savedPlaceCount }}</span><span class="stat-label">{{ __('messages.places.saved_title') }}</span></div>
    <div class="stat"><span class="stat-value">{{ $hasChatHistory ? '✓' : '—' }}</span><span class="stat-label">{{ __('messages.assistant.history') }}</span></div>
</div>

<div class="filters-actions mt-4">
    <a class="btn" href="{{ route('journey.index') }}">{{ __('messages.nav.journey') }}</a>
    <a class="btn ghost" href="{{ route('places.saved') }}">{{ __('messages.places.saved_title') }}</a>
    <a class="btn ghost" href="{{ route('assistant.index') }}">{{ __('messages.nav.assistant') }}</a>
    <a class="btn ghost" href="{{ route('profile') }}">{{ __('messages.nav.profile') }}</a>
</div>

@auth
    @if(Route::has('profile') && auth()->user()->isModerator())
        <p class="small mt-3"><a href="{{ route('admin.dashboard') }}">Open admin dashboard →</a></p>
    @endif
@endauth

<section class="section mt-5">
    <h2>{{ __('messages.dashboard.recent_trips') }}</h2>
    @if($recent->isEmpty())
        <div class="empty card">
            <span class="empty-icon" aria-hidden="true">🧭</span>
            <h3>{{ __('messages.dashboard.no_trips') }}</h3>
            <p>{{ __('messages.dashboard.no_trips_body') }}</p>
            <a class="btn" href="{{ route('journey.index') }}">{{ __('messages.nav.journey') }}</a>
        </div>
    @else
        <div class="stack">
            @foreach($recent as $itinerary)
                <article class="card">
                    <div class="card-head">
                        <h3 class="mb-2"><a href="{{ route('itineraries.show', $itinerary) }}">{{ $itinerary->title }}</a></h3>
                        <span class="badge b-{{ $itinerary->data_status ?? 'unverified' }}">{{ \App\Integrations\Support\DataStatus::label($itinerary->data_status) }}</span>
                    </div>
                    <p class="small muted">
                        {{ $itinerary->origin_city }} → {{ $itinerary->destination_city }}
                        @if($itinerary->centre) · {{ $itinerary->centre->name }} @endif
                        · {{ $itinerary->created_at?->format('d M Y') }}
                    </p>
                </article>
            @endforeach
        </div>
        <p class="mt-3"><a class="btn ghost" href="{{ route('itineraries.index') }}">All trips →</a></p>
    @endif
</section>
@endsection
