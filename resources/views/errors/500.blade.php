@extends('layouts.app')
@section('title', 'Something went wrong')
@section('robots', 'noindex,nofollow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">⚠️</span>
    <h1>{{ __('messages.errors.server_error_title') }}</h1>
    <p>{{ __('messages.errors.server_error_body') }}</p>
    <div class="filters-actions center-x">
        <a class="btn" href="{{ route('home') }}">{{ __('messages.nav.home') }}</a>
        <a class="btn ghost" href="{{ route('help.index') }}">{{ __('messages.nav.help') }}</a>
    </div>
</div>
@endsection
