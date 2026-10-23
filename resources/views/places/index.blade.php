@extends('layouts.app')
@section('title', __('messages.places.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.places.title') }}</h1>
    <p>Hotels, lodges, breakfast spots and stationery shops near your centre or arrival point. We show only curated or explicitly permitted provider listings, and we never invent hotels, prices or vacancies.</p>
</div>

@if(! $usesLiveProvider)
    <div class="alert warn">
        {{ __('messages.places.no_key') }}
        Add <code>GOOGLE_PLACES_SERVER_KEY</code> (and <code>PLACES_PROVIDER=google</code>) to enable live provider search.
        Curated admin listings always work and are the default.
    </div>
@endif

@if($originNote)
    <div class="alert info">{{ $originNote }}</div>
@endif

<form method="GET" action="{{ route('places.index') }}" class="card" role="search">
    <div class="row">
        <div>
            <label for="p-category">{{ __('messages.places.category') }}</label>
            <select id="p-category" name="category">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="p-centre">{{ __('messages.places.near_centre') }}</label>
            <select id="p-centre" name="centre_id">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($centres as $c)
                    <option value="{{ $c->id }}" @selected(optional($centre)->id === $c->id)>{{ $c->city }} — {{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="p-city">{{ __('messages.common.city') }}</label>
            <input id="p-city" name="city" type="text" value="{{ request('city') }}" placeholder="e.g. Delhi">
        </div>
        <div>
            <label for="p-q">{{ __('messages.centres.keyword') }}</label>
            <input id="p-q" name="q" type="search" value="{{ request('q') }}" placeholder="Name or street">
        </div>
        <div>
            <label for="p-radius">{{ __('messages.places.radius') }}</label>
            <select id="p-radius" name="radius">
                @foreach($radiusOptions as $option)
                    <option value="{{ $option }}" @selected($radius === $option)>{{ $option >= 1000 ? ($option / 1000).' km' : $option.' m' }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit">{{ __('messages.common.search') }}</button>
        <a class="btn ghost" href="{{ route('places.index') }}">{{ __('messages.common.clear_filters') }}</a>
    </div>
</form>

<p class="tiny muted mt-3">
    {{ __('messages.common.source') }}: {{ $providerName }}@if($result->retrievedAt) · {{ __('messages.common.last_retrieved') }} {{ \Illuminate\Support\Carbon::parse($result->retrievedAt)->format('d M Y, H:i') }}@endif
    @if($usesLiveProvider) · Ratings are provider ratings, not safety assessments. @endif
</p>

@if(! $result->isAvailable())
    <div class="alert warn">
        <strong>{{ __('messages.places.none_title') }}</strong>
        <div class="small">{{ $result->error ?? $result->sourceName }}</div>
    </div>
@endif

@if(empty($places))
    <div class="empty card">
        <span class="empty-icon" aria-hidden="true">🏨</span>
        <h3>{{ __('messages.places.none_title') }}</h3>
        <p>{{ __('messages.places.none_body') }}</p>
    </div>
@else
    <div class="grid">
        @foreach($places as $place)
            @include('places._card', ['place' => $place, 'saved' => $saved])
        @endforeach
    </div>
@endif
@endsection
