@extends('layouts.app')
@section('title', 'Down for maintenance')
@section('robots', 'noindex,nofollow')
@section('content')
<div class="empty card">
    <span class="empty-icon" aria-hidden="true">🛠️</span>
    <h1>{{ __('messages.errors.maintenance_title') }}</h1>
    <p>{{ __('messages.errors.maintenance_body') }}</p>
</div>
@endsection
