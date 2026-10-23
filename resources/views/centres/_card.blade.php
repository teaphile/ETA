@php
    /** @var \App\Models\ExamCentre $centre */
@endphp
<article class="card">
    <div class="card-head">
        <h3 class="mb-2">
            <a href="{{ route('centres.show', $centre) }}">{{ $centre->name }}</a>
        </h3>
        <span class="badge b-{{ $centre->data_status }}">{{ $centre->statusLabel() }}</span>
    </div>

    <p class="small muted mb-2">
        <span class="mono">{{ $centre->centre_code }}</span>
        @if($centre->examination) · {{ $centre->examination->name }} @endif
    </p>

    <p class="small">
        {{ $centre->city }}@if($centre->state), {{ $centre->state }}@endif
        @if($centre->pin_code) — {{ $centre->pin_code }}@endif
    </p>

    @if($centre->is_demo)
        <span class="badge b-demo">{{ __('messages.common.demo') }}</span>
        <span class="small muted">Demonstration record — not a real examination centre.</span>
    @endif

    @if(! $centre->hasCoordinates())
        <p class="small muted mt-2">{{ __('messages.centres.coordinates_missing') }}</p>
    @endif

    <div class="card-actions">
        <a class="btn sm" href="{{ route('centres.show', $centre) }}">{{ __('messages.common.view_details') }}</a>
        <a class="btn sm ghost" href="{{ route('journey.index', ['centre_id' => $centre->id]) }}">{{ __('messages.common.plan_journey') }}</a>
    </div>
</article>
