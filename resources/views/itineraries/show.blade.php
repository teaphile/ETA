@extends('layouts.app')
@section('title', $itinerary->title)

@section('content')
<div class="breadcrumb"><a href="{{ route('itineraries.index') }}">{{ __('messages.nav.trips') }}</a> / {{ \Illuminate\Support\Str::limit($itinerary->title, 60) }}</div>

@php
    $plan = is_array($itinerary->options_snapshot) ? \App\Integrations\Support\JourneyPlan::fromArray($itinerary->options_snapshot) : null;
    $limitations = [];
    if (is_string($itinerary->data_limitations) && $itinerary->data_limitations !== '') {
        $decoded = json_decode($itinerary->data_limitations, true);
        $limitations = is_array($decoded) ? $decoded : [];
    }
@endphp

<div class="page-head">
    <h1>{{ $itinerary->title }}</h1>
    <p class="small muted">
        {{ __('messages.journey.saved_on') }} {{ $itinerary->created_at?->format('d M Y') }}
        @if($itinerary->departure_at) · {{ __('messages.journey.departure') }} {{ $itinerary->departure_at->format('d M Y') }} @endif
        @if($itinerary->budget) · {{ __('messages.journey.budget') }} ₹{{ number_format((float) $itinerary->budget) }} @endif
    </p>
</div>

<div class="panel panel-soft">
    <span class="badge b-{{ $itinerary->data_status ?? 'unverified' }}">{{ \App\Integrations\Support\DataStatus::label($itinerary->data_status) }}</span>
    <p class="tiny muted mt-2 mb-0">
        <span class="badge b-estimated">{{ __('messages.common.estimated') }}</span>
        {{ __('messages.journey.snapshot_note') }}
    </p>
</div>

@if($itinerary->centre)
    <section class="card">
        <h2 class="mt-0">{{ __('messages.journey.destination') }}</h2>
        <p>
            <strong>{{ $itinerary->centre->name }}</strong>
            (<span class="mono">{{ $itinerary->centre->centre_code }}</span>)
            @if($itinerary->centre->examination) · {{ $itinerary->centre->examination->name }} @endif
        </p>
        <p class="small muted">
            {{ $itinerary->origin_city }} → {{ $itinerary->destination_city }}
            @if($itinerary->preferred_modes) · {{ __('messages.journey.modes') }}: {{ implode(', ', $itinerary->modeLabels()) }} @endif
        </p>
        <div class="card-actions">
            <a class="btn sm" href="{{ route('centres.show', $itinerary->centre) }}">{{ __('messages.common.view_details') }}</a>
            <a class="btn sm ghost" href="{{ route('journey.index', ['centre_id' => $itinerary->centre_id]) }}">{{ __('messages.journey.replan') }}</a>
        </div>
    </section>
@endif

@if($limitations !== [])
    <details class="disclosure panel panel-warn">
        <summary>{{ __('messages.journey.recorded_limitations') }}</summary>
        <ul>
            @foreach($limitations as $limitation)
                <li>{{ is_array($limitation) ? ($limitation['message'] ?? '') : $limitation }}</li>
            @endforeach
        </ul>
    </details>
@endif

@if($plan && $plan->hasOptions())
    <h2>{{ __('messages.journey.stage_a') }}</h2>
    <div class="tablewrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('messages.journey.mode') }}</th>
                    <th scope="col">{{ __('messages.journey.duration') }}</th>
                    <th scope="col">{{ __('messages.journey.price') }}</th>
                    <th scope="col">{{ __('messages.journey.status_and_method') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->intercityOptions as $option)
                    <tr>
                        <td><strong>{{ $option->label }}</strong>@if($option->distanceKm !== null)<br><span class="small muted">{{ $option->distanceKm }} km</span>@endif</td>
                        <td>{{ $option->durationHours ? $option->durationHours.' hrs (approx.)' : '—' }}</td>
                        <td>
                            @if($option->hasPrice())
                                ₹{{ number_format($option->priceMin) }} – ₹{{ number_format((int) $option->priceMax) }}
                                <span class="badge b-estimated">{{ __('messages.common.estimated') }}</span>
                            @else
                                <span class="muted">{{ __('messages.common.price_unavailable') }}</span>
                            @endif
                        </td>
                        <td class="small muted">{{ $option->estimateMethod }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($plan->lastMileOptions !== [])
        <h2>{{ __('messages.journey.stage_b') }}</h2>
        <div class="tablewrap">
            <table>
                <thead><tr><th scope="col">{{ __('messages.journey.mode') }}</th><th scope="col">{{ __('messages.journey.duration') }}</th><th scope="col">{{ __('messages.journey.price') }}</th></tr></thead>
                <tbody>
                    @foreach($plan->lastMileOptions as $option)
                        <tr>
                            <td><strong>{{ $option->label }}</strong></td>
                            <td>{{ $option->durationHours !== null ? '~'.round($option->durationHours * 60).' min' : '—' }}</td>
                            <td>@if($option->hasPrice())₹~{{ number_format($option->priceMin) }} <span class="badge b-{{ $option->priceStatus }}">{{ \App\Integrations\Support\DataStatus::label($option->priceStatus) }}</span>@else<span class="muted">{{ __('messages.common.unavailable') }}</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@else
    <div class="alert warn">{{ __('messages.journey.no_snapshot') }}</div>
@endif

<div class="card-actions">
    <button class="btn ghost" type="button" data-print>🖨️ {{ __('messages.common.print') }}</button>
    <form method="POST" action="{{ route('itineraries.destroy', $itinerary) }}">
        @csrf
        @method('DELETE')
        <button class="btn danger" type="submit">{{ __('messages.common.delete') }}</button>
    </form>
</div>
@endsection
