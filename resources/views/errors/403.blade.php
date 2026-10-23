@extends('layouts.app')
@section('title', 'Not allowed')
@section('robots', 'noindex,nofollow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">🔒</span>
    <h1>{{ __('messages.errors.forbidden_title') }}</h1>
    <p>{{ __('messages.errors.forbidden_body') }}</p>
    <div class="filters-actions center-x">
        <a class="btn" href="{{ route('home') }}">{{ __('messages.nav.home') }}</a>
        <a class="btn ghost" href="{{ route('login') }}">{{ __('messages.nav.login') }}</a>
    </div>
</div>
@endsection
