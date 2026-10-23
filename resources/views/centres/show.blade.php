@extends('layouts.app')
@section('title', $centre->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('centres.index') }}">{{ __('messages.nav.centres') }}</a> /
    @if($centre->examination){{ $centre->examination->name }} / @endif
    {{ $centre->city }}
</div>

<div class="page-head">
    <div class="card-head">
        <div>
            <h1 class="mb-2">{{ $centre->name }}</h1>
            <p class="small muted mb-0">
                <span class="mono">{{ $centre->centre_code }}</span>
                @if($centre->examination) · {{ $centre->examination->name }} @endif
                @if($centre->session) · {{ $centre->session->name }}@if($centre->session->year) {{ $centre->session->year }}@endif @endif
            </p>
        </div>
        <div class="stack-sm">
            <span class="badge b-{{ $centre->data_status }}">{{ $centre->statusLabel() }}</span>
            @if($centre->is_demo)<span class="badge b-demo">{{ __('messages.common.demo') }}</span>@endif
        </div>
    </div>
</div>

<div class="alert warn">{{ __('messages.centres.warning') }}</div>

<div class="grid-2">
    <section class="card">
        <h2 class="mt-0">Address &amp; verification</h2>
        <p>{{ $centre->fullAddress() }}</p>

        <dl class="stack-sm">
            <dt class="small muted">{{ __('messages.common.source') }}</dt>
            <dd>{{ $centre->source_name ?: __('messages.common.no_information') }}</dd>

            @if($centre->source_url)
                <dt class="small muted">{{ __('messages.centres.evidence_link') }}</dt>
                <dd><a href="{{ $centre->source_url }}" target="_blank" rel="noopener nofollow">{{ $centre->source_url }} ↗</a></dd>
            @endif

            <dt class="small muted">{{ __('messages.common.last_verified') }}</dt>
            <dd>{{ $centre->last_verified_at ? $centre->last_verified_at->format('d M Y') : __('messages.common.not_verified') }}</dd>

            @if($centre->error_or_limitation)
                <dt class="small muted">{{ __('messages.centres.limitations') }}</dt>
                <dd>{{ $centre->error_or_limitation }}</dd>
            @endif

            @if($centre->examination?->official_url)
                <dt class="small muted">{{ __('messages.centres.official_site') }}</dt>
                <dd><a href="{{ $centre->examination->official_url }}" target="_blank" rel="noopener nofollow">{{ $centre->examination->official_url }} ↗</a></dd>
            @endif
        </dl>

        <div class="card-actions">
            <a class="btn" href="{{ route('journey.index', ['centre_id' => $centre->id]) }}">{{ __('messages.common.plan_journey') }}</a>
            <a class="btn ghost" href="{{ $centre->mapsDirectionsUrl() }}" target="_blank" rel="noopener nofollow">{{ __('messages.common.open_in_maps') }} ↗</a>
        </div>
    </section>

    <section class="card">
        <h2 class="mt-0">{{ __('messages.centres.location') }}</h2>
        @if($centre->hasCoordinates())
            <iframe class="map-embed" loading="lazy" title="Map showing {{ $centre->name }}"
                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $centre->longitude - 0.01 }}%2C{{ $centre->latitude - 0.01 }}%2C{{ $centre->longitude + 0.01 }}%2C{{ $centre->latitude + 0.01 }}&amp;layer=mapnik&amp;marker={{ $centre->latitude }}%2C{{ $centre->longitude }}"></iframe>
            <p class="tiny muted">Map data © OpenStreetMap contributors. Coordinates are recorded from the centre's stored source.</p>
        @else
            <p class="muted">{{ __('messages.centres.coordinates_missing') }}</p>
        @endif
    </section>
</div>

<section class="section">
    <h2>{{ __('messages.centres.nearby_title') }}</h2>
    @if($nearbyResult && ! $nearbyResult->isAvailable())
        <div class="alert warn">
            <strong>{{ __('messages.places.none_title') }}</strong>
            <div class="small">{{ $nearbyResult->error ?? $nearbyResult->sourceName }}</div>
        </div>
    @elseif(empty($nearby))
        <div class="empty card">
            <span class="empty-icon" aria-hidden="true">🏨</span>
            <h3>{{ __('messages.places.none_title') }}</h3>
            <p>{{ __('messages.places.none_body') }}</p>
        </div>
    @else
        <div class="grid">
            @foreach($nearby as $place)
                @include('places._card', ['place' => $place])
            @endforeach
        </div>
    @endif
    <p class="mt-4"><a class="btn ghost" href="{{ route('places.index', ['centre_id' => $centre->id]) }}">{{ __('messages.common.nearby_services') }}</a></p>
</section>
@endsection
