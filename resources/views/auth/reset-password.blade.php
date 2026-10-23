@extends('layouts.app')
@section('title', __('messages.auth.reset_title'))

@section('content')
<div class="card narrow">
    <h1 class="mt-0">{{ __('messages.auth.reset_title') }}</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email">{{ __('messages.auth.email') }}</label>
            <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email', $email) }}"
                   @error('email') aria-invalid="true" @enderror>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="mt-3">
            <label for="password">{{ __('messages.auth.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   @error('password') aria-invalid="true" @enderror>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="mt-3">
            <label for="password_confirmation">{{ __('messages.auth.password_confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>

        <div class="card-actions">
            <button class="btn block" type="submit">{{ __('messages.auth.reset_submit') }}</button>
        </div>
    </form>
</div>
@endsection
