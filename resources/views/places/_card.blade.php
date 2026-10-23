@php
    /** @var \App\Integrations\Support\PlaceResult $place */
    $categoryLabel = config('exam_travel.service_categories.'.$place->category, ucfirst(str_replace('_', ' ', $place->category)));
    $lid = $place->localServiceId;
    $isSaved = isset($saved) && $lid !== null && in_array($lid, $saved, true);
    $canSave = auth()->check() && $lid !== null;
    $maps = $place->mapsUrl();
@endphp
<article class="card">
    <div class="card-head">
        <h3 class="mb-2">{{ $place->name }}</h3>
        <span class="badge b-{{ $place->dataStatus }}">{{ \App\Integrations\Support\DataStatus::label($place->dataStatus) }}</span>
    </div>

    <p class="small muted mb-2">
        {{ $categoryLabel }}
        @if($place->city) · {{ $place->city }} @endif
        @if($place->distanceKm !== null) · {{ __('messages.places.distance', ['km' => $place->distanceKm]) }} @endif
    </p>

    @if($place->address)
        <p class="small">{{ $place->address }}</p>
    @endif

    <p class="small">
        @if($place->hasPrice())
            <strong>{{ __('messages.places.price_from', ['price' => number_format($place->priceFrom)]) }}</strong>
        @else
            <span class="muted">{{ __('messages.places.price_unknown') }}</span>
        @endif
    </p>

    @if($place->rating !== null)
        <p class="small">
            {{ __('messages.places.provider_rating') }}: <strong>{{ number_format($place->rating, 1) }}</strong>@if($place->reviewsCount) ({{ $place->reviewsCount }})@endif
            <span class="hint">{{ __('messages.places.rating_disclaimer') }}</span>
        </p>
    @endif

    @if($place->openingHours)
        <p class="small muted">{{ __('messages.places.opening_hours') }}: {{ $place->openingHours }}</p>
    @endif

    @if($place->isDemo)
        <span class="badge b-demo">{{ __('messages.common.demo') }}</span>
    @endif

    <p class="tiny muted mt-2">
        {{ __('messages.common.source') }}: {{ $place->sourceName }}
        @if($place->lastVerifiedAt) · {{ __('messages.common.last_verified') }} {{ \Illuminate\Support\Carbon::parse($place->lastVerifiedAt)->format('d M Y') }}@endif
    </p>

    @if($place->errorOrLimitation)
        <p class="tiny muted">{{ $place->errorOrLimitation }}</p>
    @endif

    <div class="card-actions">
        @if($maps)
            <a class="btn sm ghost" href="{{ $maps }}" target="_blank" rel="noopener nofollow">{{ __('messages.common.open_in_maps') }} ↗</a>
        @endif
        @if($place->phone)
            <a class="btn sm ghost" href="tel:{{ preg_replace('/[^\d+]/', '', $place->phone) }}">☎ {{ $place->phone }}</a>
        @endif
        @if($place->website)
            <a class="btn sm ghost" href="{{ $place->website }}" target="_blank" rel="noopener nofollow">Website ↗</a>
        @endif

        @if($canSave)
            @if($isSaved)
                <form method="POST" action="{{ route('places.unsave', $lid) }}">
                    @csrf
                    <button class="btn sm danger" type="submit">{{ __('messages.common.unsave') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('places.save', $lid) }}">
                    @csrf
                    <button class="btn sm" type="submit">{{ __('messages.places.save') }}</button>
                </form>
            @endif
        @elseif(! auth()->check() && $lid !== null)
            <a class="btn sm ghost" href="{{ route('login') }}">{{ __('messages.places.save') }}</a>
        @endif
    </div>
</article>
