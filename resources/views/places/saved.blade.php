@extends('layouts.app')
@section('title', __('messages.places.saved_title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.places.saved_title') }}</h1>
    <p class="muted">{{ __('messages.places.none_body') }}</p>
</div>

@php
    $savedIds = collect($places)->pluck('localServiceId')->filter()->values()->all();
@endphp

@if(empty($places))
    <div class="empty card">
        <span class="empty-icon" aria-hidden="true">⭐</span>
        <h3>{{ __('messages.places.none_title') }}</h3>
        <p>{{ __('messages.places.none_body') }}</p>
        <div class="card-actions">
            <a class="btn" href="{{ route('places.index') }}">{{ __('messages.nav.places') }}</a>
        </div>
    </div>
@else
    <div class="grid">
        @foreach($places as $place)
            @include('places._card', ['place' => $place, 'saved' => $savedIds])
        @endforeach
    </div>
    <p class="mt-4"><a class="btn ghost" href="{{ route('places.index') }}">{{ __('messages.nav.places') }}</a></p>
@endif
@endsection
