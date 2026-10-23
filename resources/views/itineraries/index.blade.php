@extends('layouts.app')
@section('title', __('messages.nav.trips'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.nav.trips') }}</h1>
    <p class="muted">Saved journey plans. Snapshots are stored as they were, with their data status and limitations.</p>
</div>

@if($items->isEmpty())
    <div class="empty card">
        <span class="empty-icon" aria-hidden="true">🧭</span>
        <h3>{{ __('messages.dashboard.no_trips') }}</h3>
        <p>{{ __('messages.dashboard.no_trips_body') }}</p>
        <a class="btn" href="{{ route('journey.index') }}">{{ __('messages.nav.journey') }}</a>
    </div>
@else
    <div class="stack">
        @foreach($items as $itinerary)
            <article class="card">
                <div class="card-head">
                    <h3 class="mb-2"><a href="{{ route('itineraries.show', $itinerary) }}">{{ $itinerary->title }}</a></h3>
                    <span class="badge b-{{ $itinerary->data_status ?? 'unverified' }}">{{ \App\Integrations\Support\DataStatus::label($itinerary->data_status) }}</span>
                </div>
                <p class="small muted">
                    {{ $itinerary->origin_city }} → {{ $itinerary->destination_city }}
                    @if($itinerary->centre) · <span class="mono">{{ $itinerary->centre->centre_code }}</span> {{ $itinerary->centre->name }}@endif
                    · {{ $itinerary->created_at?->format('d M Y') }}
                    @if($itinerary->budget) · {{ __('messages.journey.budget') }}: ₹{{ number_format((float) $itinerary->budget) }}@endif
                </p>
                <div class="card-actions">
                    <a class="btn sm" href="{{ route('itineraries.show', $itinerary) }}">{{ __('messages.common.view_details') }}</a>
                    <form method="POST" action="{{ route('itineraries.destroy', $itinerary) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn sm danger" type="submit">{{ __('messages.common.delete') }}</button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
    <div class="pager">{{ $items->links() }}</div>
@endif
@endsection
