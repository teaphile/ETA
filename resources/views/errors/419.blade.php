@extends('layouts.app')
@section('title', 'Session expired')
@section('robots', 'noindex,nofollow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">⏳</span>
    <h1>{{ __('messages.errors.session_expired_title') }}</h1>
    <p>{{ __('messages.errors.session_expired_body') }}</p>
    <div class="filters-actions center-x">
        <a class="btn" href="{{ route('home') }}">{{ __('messages.nav.home') }}</a>
    </div>
</div>
@endsection
