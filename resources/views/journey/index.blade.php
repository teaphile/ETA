@extends('layouts.app')
@section('title', __('messages.journey.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.journey.title') }}</h1>
    <p>{{ __('messages.journey.intro') }}</p>
</div>

@if($centres->isEmpty())
    <div class="alert warn">
        {{ __('messages.home.no_centres_body') }}
        <a href="{{ route('centres.index') }}">{{ __('messages.nav.centres') }}</a>
    </div>
@else
<div class="steps">
    <span class="step is-active">{{ __('messages.home.step1_title') }}</span>
    <span class="step is-active">{{ __('messages.home.step2_title') }}</span>
    <span class="step">{{ __('messages.home.step3_title') }}</span>
</div>

<form method="POST" action="{{ route('journey.plan') }}" class="card">
    @csrf

    <h2 class="mt-0">{{ __('messages.journey.destination') }}</h2>
    <div class="row">
        <div>
            <label for="j-centre">{{ __('messages.journey.destination') }} <span class="req">*</span></label>
            <select id="j-centre" name="centre_id" required>
                <option value="">— {{ __('messages.journey.destination') }} —</option>
                @foreach($centres->groupBy(fn ($c) => $c->examination?->name ?? 'Other') as $group => $groupCentres)
                    <optgroup label="{{ $group }}">
                        @foreach($groupCentres as $c)
                            <option value="{{ $c->id }}" @selected($selectedCentre?->id === $c->id)>{{ $c->city }} — {{ $c->name }} ({{ $c->centre_code }})</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>

    <h2>{{ __('messages.journey.origin') }}</h2>
    <div class="row">
        <div>
            <label for="j-origin">{{ __('messages.journey.origin') }} <span class="req">*</span></label>
            <input id="j-origin" name="origin_city" type="text" list="origin-cities" required
                   value="{{ old('origin_city', auth()->user()->current_city ?? '') }}" placeholder="e.g. Patna">
            <datalist id="origin-cities">
                @foreach($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
            <span class="datalist-note">A city from the reference directory gives the most accurate distance estimate. If your city is missing, add a fuller starting address below.</span>
        </div>
        <div>
            <label for="j-address">{{ __('messages.journey.origin_address') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="j-address" name="origin_address" type="text" maxlength="200" value="{{ old('origin_address') }}" placeholder="{{ __('messages.journey.origin_address_hint') }}">
        </div>
    </div>

    <h2>{{ __('messages.journey.details') }}</h2>
    <div class="row">
        <div>
            <label for="j-date">{{ __('messages.journey.departure') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="j-date" name="departure_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('departure_date') }}">
        </div>
        <div>
            <label for="j-budget">{{ __('messages.journey.budget') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="j-budget" name="budget" type="number" min="0" max="500000" step="50" value="{{ old('budget') }}" placeholder="e.g. 1500">
        </div>
    </div>

    <fieldset class="fieldset-clean mt-4">
        <legend class="label">{{ __('messages.journey.modes') }}</legend>
        @php $selectedModes = old('modes', ['train', 'bus', 'flight', 'car']); @endphp
        @foreach(['train' => 'Train', 'bus' => 'Bus', 'flight' => 'Flight', 'car' => 'Personal vehicle / cab'] as $mode => $label)
            <div class="checkbox-row">
                <input type="checkbox" id="mode-{{ $mode }}" name="modes[]" value="{{ $mode }}" @checked(in_array($mode, $selectedModes, true))>
                <label for="mode-{{ $mode }}">{{ $label }}</label>
            </div>
        @endforeach
    </fieldset>

    @auth
        <div class="checkbox-row mt-3">
            <input type="checkbox" id="j-save" name="save" value="1" @checked(old('save'))>
            <label for="j-save">{{ __('messages.journey.save_plan') }}</label>
        </div>
    @endauth

    <div class="filters-actions">
        <button class="btn amber" type="submit">{{ __('messages.journey.submit') }}</button>
    </div>
    <p class="tiny muted mt-3">{{ __('messages.journey.estimated_badge_note') }} {{ __('messages.footer.verify') }}</p>
</form>
@endif
@endsection
