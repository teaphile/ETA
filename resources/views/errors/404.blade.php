@extends('layouts.app')
@section('title', 'Not found')
@section('robots', 'noindex,follow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">🧭</span>
    <h1>{{ __('messages.errors.not_found_title') }}</h1>
    <p>{{ __('messages.errors.not_found_body') }}</p>
    <div class="filters-actions center-x">
        <a class="btn" href="{{ route('centres.index') }}">{{ __('messages.nav.centres') }}</a>
        <a class="btn ghost" href="{{ route('home') }}">{{ __('messages.nav.home') }}</a>
    </div>
</div>
@endsection
