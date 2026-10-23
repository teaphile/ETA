@extends('layouts.app')
@section('title', 'Too many requests')
@section('robots', 'noindex,nofollow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">🐢</span>
    <h1>{{ __('messages.errors.too_many_title') }}</h1>
    <p>{{ __('messages.errors.too_many_body') }}</p>
    <div class="filters-actions center-x">
        <a class="btn" href="{{ route('home') }}">{{ __('messages.nav.home') }}</a>
    </div>
</div>
@endsection
