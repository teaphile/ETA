@extends('layouts.app')
@section('title', __('messages.centres.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.centres.title') }}</h1>
    <p>{{ __('messages.centres.intro') }}</p>
</div>

<div class="alert warn">{{ __('messages.centres.warning') }}</div>

<form method="GET" action="{{ route('centres.index') }}" class="card" role="search">
    <div class="row">
        <div>
            <label for="f-exam">{{ __('messages.centres.examination') }}</label>
            <select id="f-exam" name="exam">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($examinations as $exam)
                    <option value="{{ $exam->id }}" @selected($filters['exam'] === $exam->id)>{{ $exam->name }} ({{ $exam->centres_count }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-state">{{ __('messages.common.state') }}</label>
            <select id="f-state" name="state">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($states as $state)
                    <option value="{{ $state }}" @selected($filters['state'] === $state)>{{ $state }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-city">{{ __('messages.common.city') }}</label>
            <input id="f-city" name="city" type="text" list="city-list" value="{{ $filters['city'] }}" placeholder="e.g. Delhi">
            <datalist id="city-list">
                @foreach($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>
    <div class="row mt-3">
        <div>
            <label for="f-code">{{ __('messages.centres.centre_code') }}</label>
            <input id="f-code" name="code" type="text" value="{{ $filters['code'] }}" placeholder="e.g. DEMO-DEL-101">
        </div>
        <div>
            <label for="f-q">{{ __('messages.centres.keyword') }}</label>
            <input id="f-q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="{{ __('messages.centres.keyword_hint') }}">
        </div>
        <div>
            <label for="f-sort">{{ __('messages.common.sort') }}</label>
            <select id="f-sort" name="sort">
                <option value="city" @selected($filters['sort'] === 'city')>{{ __('messages.common.city') }}</option>
                <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                <option value="recent" @selected($filters['sort'] === 'recent')>{{ __('messages.centres.most_recent') }}</option>
            </select>
        </div>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit">{{ __('messages.common.search') }}</button>
        @if($hasFilters)
            <a class="btn ghost" href="{{ route('centres.index') }}">{{ __('messages.common.clear_filters') }}</a>
        @endif
    </div>
</form>

<p class="muted mt-4">{{ __('messages.centres.found', ['count' => $centres->total()]) }}</p>

@if($centres->isEmpty())
    <div class="empty card">
        <span class="empty-icon" aria-hidden="true">🔍</span>
        <h3>{{ __('messages.centres.none_title') }}</h3>
        <p>{{ __('messages.centres.none_body') }}</p>
        <div class="card-actions">
            <a class="btn ghost" href="{{ route('centres.index') }}">{{ __('messages.common.clear_filters') }}</a>
        </div>
    </div>
@else
    <div class="grid">
        @foreach($centres as $centre)
            @include('centres._card', ['centre' => $centre])
        @endforeach
    </div>
    <div class="pager">{{ $centres->links() }}</div>
@endif
@endsection
