@extends('layouts.app')
@section('title', __('messages.auth.forgot_title'))

@section('content')
<div class="card narrow">
    <h1 class="mt-0">{{ __('messages.auth.forgot_title') }}</h1>
    <p class="muted">{{ __('messages.auth.forgot_hint') }}</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label for="email">{{ __('messages.auth.email') }}</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="email" value="{{ old('email') }}"
                   @error('email') aria-invalid="true" @enderror>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="card-actions">
            <button class="btn block" type="submit">{{ __('messages.auth.send_reset_link') }}</button>
        </div>
    </form>

    <p class="small mt-3">
        <a href="{{ route('login') }}">← {{ __('messages.auth.login_title') }}</a>
    </p>
</div>
@endsection
