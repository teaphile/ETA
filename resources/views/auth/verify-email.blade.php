@extends('layouts.app')
@section('title', __('messages.auth.verify_title'))

@section('content')
<div class="card narrow">
    <h1 class="mt-0">{{ __('messages.auth.verify_title') }}</h1>
    <p class="muted">{{ __('messages.auth.verify_body') }}</p>

    @if(session('status'))
        <div class="alert info" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="card-actions">
            <button class="btn" type="submit">{{ __('messages.auth.verify_resend') }}</button>
        </div>
    </form>

    <p class="small mt-3">
        <a href="{{ route('profile') }}">{{ __('messages.nav.profile') }}</a> ·
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button class="btn sm ghost" type="submit">{{ __('messages.nav.logout') }}</button>
        </form>
    </p>
</div>
@endsection
