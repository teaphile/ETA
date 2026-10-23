@extends('layouts.app')
@section('title', __('messages.journey.title'))

@section('content')
<div class="breadcrumb"><a href="{{ route('journey.index', ['centre_id' => $centre->id]) }}">← {{ __('messages.journey.title') }}</a></div>

<div class="page-head">
    <h1>{{ $origin }} → {{ $centre->city }}</h1>
    <p class="small muted">
        {{ __('messages.journey.destination') }}:
        <strong>{{ $centre->name }}</strong> (<span class="mono">{{ $centre->centre_code }}</span>)
        @if($departure) · {{ __('messages.common.date') }}: {{ \Illuminate\Support\Carbon::parse($departure)->format('d M Y') }} @endif
        @if($budget) · {{ __('messages.journey.budget') }}: ₹{{ number_format($budget) }} @endif
    </p>
</div>

@if($plan->unavailableReason)
    <div class="alert warn">
        <strong>{{ __('messages.journey.no_options_title') }}</strong>
        <div class="small">{{ $plan->unavailableReason }}</div>
        <div class="small">{{ __('messages.journey.no_options_body') }}</div>
    </div>
@endif

@if($plan->hasOptions())
    <div class="alert info">
        <strong>{{ __('messages.journey.how_to_read') }}:</strong>
        {{ __('messages.journey.how_to_read_body', ['straight' => number_format($plan->straightKm ?? 0), 'road' => number_format($plan->roadKm ?? 0)]) }}
        {{ __('messages.journey.lowest_note') }}
    </div>

    @if($plan->limitations !== [])
        <details class="disclosure panel panel-warn">
            <summary>{{ __('messages.journey.limitations_title') }}</summary>
            <ul>
                @foreach($plan->limitations as $limitation)
                    <li>{{ is_array($limitation) ? ($limitation['message'] ?? '') : $limitation }}</li>
                @endforeach
            </ul>
        </details>
    @endif

    @if($plan->resolutionNotes !== [])
        <details class="disclosure panel panel-soft">
            <summary>{{ __('messages.journey.resolution_title') }}</summary>
            <ul>
                @foreach($plan->resolutionNotes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </details>
    @endif

    <h2>{{ __('messages.journey.stage_a') }}</h2>
    <div class="tablewrap">
        <table>
            <caption>{{ $plan->providerName ? ('Provider: '.$plan->providerName) : 'Estimated planning ranges' }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ __('messages.journey.mode') }}</th>
                    <th scope="col">{{ __('messages.journey.duration') }}</th>
                    <th scope="col">{{ __('messages.journey.price') }}</th>
                    <th scope="col">{{ __('messages.journey.status_and_method') }}</th>
                    <th scope="col">{{ __('messages.journey.book_or_verify') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->intercityOptions as $option)
                    <tr>
                        <td>
                            <strong>{{ $option->label }}</strong>
                            @if($option->distanceKm !== null)<br><span class="small muted">{{ $option->distanceKm }} km</span>@endif
                        </td>
                        <td>{{ $option->durationHours ? $option->durationHours.' hrs (approx.)' : '—' }}</td>
                        <td>
                            @if($option->hasPrice())
                                ₹{{ number_format($option->priceMin) }} – ₹{{ number_format((int) $option->priceMax) }}
                                <span class="badge b-estimated">{{ __('messages.common.estimated') }}</span>
                                @if($budget && $option->priceMin > $budget)
                                    <br><span class="badge b-unverified">{{ __('messages.journey.above_budget', ['budget' => number_format($budget)]) }}</span>
                                @endif
                            @else
                                <span class="muted">{{ __('messages.journey.price') }} {{ mb_strtolower(__('messages.common.unavailable')) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge b-{{ $option->priceStatus }}">{{ \App\Integrations\Support\DataStatus::label($option->priceStatus) }}</span>
                            @if($option->estimateMethod)<br><span class="small muted">{{ $option->estimateMethod }}</span>@endif
                            @if($option->note)<br><span class="small muted">{{ $option->note }}</span>@endif
                        </td>
                        <td>
                            @if($option->bookingUrl)
                                <a class="btn sm" href="{{ $option->bookingUrl }}" target="_blank" rel="noopener nofollow">{{ $option->providerName ?: 'Official site' }} ↗</a>
                                <br><span class="tiny muted">{{ __('messages.journey.external_note') }}</span>
                            @else
                                <span class="muted small">{{ __('messages.common.no_information') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($plan->lastMileOptions !== [])
        <h2>{{ __('messages.journey.stage_b') }}</h2>
        <p class="muted">
            {{ __('messages.journey.stage_b_help', ['km' => $plan->lastMileKm !== null ? $plan->lastMileKm.' km' : '?']) }}
        </p>
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
                    @foreach($plan->lastMileOptions as $option)
                        <tr>
                            <td><strong>{{ $option->label }}</strong></td>
                            <td>{{ $option->durationHours !== null ? '~'.round($option->durationHours * 60).' min' : '—' }}</td>
                            <td>
                                @if($option->hasPrice())
                                    ₹~{{ number_format($option->priceMin) }}
                                    <span class="badge b-{{ $option->priceStatus }}">{{ \App\Integrations\Support\DataStatus::label($option->priceStatus) }}</span>
                                @else
                                    <span class="muted">{{ __('messages.common.price_unavailable') }}</span>
                                @endif
                            </td>
                            <td class="small muted">{{ $option->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card-actions">
        <a class="btn" href="{{ $centre->mapsDirectionsUrl($origin) }}" target="_blank" rel="noopener nofollow">🗺️ {{ __('messages.common.open_in_maps') }} ↗</a>
        <a class="btn ghost" href="{{ route('places.index', ['centre_id' => $centre->id, 'radius' => $suggestedRadius]) }}">{{ __('messages.common.nearby_services') }}</a>
        <a class="btn ghost" href="{{ route('journey.index', ['centre_id' => $centre->id]) }}">{{ __('messages.journey.modify') }}</a>
        <button class="btn ghost" type="button" data-print>🖨️ {{ __('messages.common.print') }}</button>
    </div>
@else
    <div class="card">
        <h3 class="mt-0">{{ __('messages.journey.next_steps') }}</h3>
        <p>🚆 Trains: <a href="https://www.irctc.co.in" target="_blank" rel="noopener nofollow">IRCTC ↗</a>
            · 🚌 Bus: your State RTC portal
            · ✈️ Flights: airline websites
            · 🗺️ Route: <a href="{{ $centre->mapsDirectionsUrl($origin) }}" target="_blank" rel="noopener nofollow">Google Maps ↗</a></p>
        <p class="small muted">{{ __('messages.journey.no_ticketing') }}</p>
    </div>
@endif

@auth
    @if($saved && $savedItinerary)
        <div class="alert success">{{ __('messages.journey.saved') }} <a href="{{ route('itineraries.show', $savedItinerary) }}">{{ __('messages.nav.trips') }} →</a></div>
    @endif
@else
    <div class="card mt-4">
        <strong>{{ __('messages.journey.save_plan') }}?</strong>
        <p class="small muted mb-2"><a href="{{ route('register') }}">{{ __('messages.nav.register') }}</a> {{ __('messages.auth.no_account') }}</p>
    </div>
@endauth

<p class="tiny muted mt-5">{{ __('messages.footer.estimates') }} {{ __('messages.footer.verify') }}</p>
@endsection
