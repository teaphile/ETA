@extends('layouts.app')
@section('title', __('messages.auth.login_title'))

@section('content')
<div class="grid-2">
    <section class="card">
        <h1 class="mt-0">{{ __('messages.auth.login_title') }}</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div>
                <label for="email">{{ __('messages.auth.email') }}</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="email" value="{{ old('email') }}"
                       @error('email') aria-invalid="true" @enderror>
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="mt-3">
                <label for="password">{{ __('messages.auth.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       @error('password') aria-invalid="true" @enderror>
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="checkbox-row mt-3">
                <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                <label for="remember">{{ __('messages.auth.remember') }}</label>
            </div>

            <div class="card-actions">
                <button class="btn block" type="submit">{{ __('messages.auth.login_title') }}</button>
            </div>
        </form>

        <p class="small mt-3">
            <a href="{{ route('password.request') }}">{{ __('messages.auth.forgot') }}</a>
        </p>
    </section>

    <section class="card">
        <h2 class="mt-0">{{ __('messages.auth.no_account') }}</h2>
        <p class="muted">{{ __('messages.home.hero_body') }}</p>
        <div class="card-actions">
            <a class="btn amber" href="{{ route('register') }}">{{ __('messages.auth.register_title') }}</a>
        </div>
    </section>
</div>
@endsection
