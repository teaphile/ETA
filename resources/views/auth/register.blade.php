@extends('layouts.app')
@section('title', __('messages.auth.register_title'))

@section('content')
<h1>{{ __('messages.auth.register_title') }}</h1>
<p class="muted">{{ __('messages.centres.intro') }} {{ __('messages.home.hero_body') }}</p>

<form method="POST" action="{{ route('register') }}" class="card">
    @csrf

    <div class="row">
        <div>
            <label for="name">{{ __('messages.auth.name') }} <span class="req">*</span></label>
            <input id="name" name="name" type="text" required autofocus autocomplete="name" minlength="2" maxlength="120" value="{{ old('name') }}"
                   @error('name') aria-invalid="true" @enderror>
            @error('name')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="email">{{ __('messages.auth.email') }} <span class="req">*</span></label>
            <input id="email" name="email" type="email" required autocomplete="email" maxlength="190" value="{{ old('email') }}"
                   @error('email') aria-invalid="true" @enderror>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="row mt-3">
        <div>
            <label for="phone">{{ __('messages.auth.phone') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="16" value="{{ old('phone') }}"
                   @error('phone') aria-invalid="true" @enderror>
            @error('phone')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="current_city">{{ __('messages.auth.current_city') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="current_city" name="current_city" type="text" maxlength="100" value="{{ old('current_city') }}">
        </div>
        <div>
            <label for="state">{{ __('messages.common.state') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <input id="state" name="state" type="text" maxlength="100" value="{{ old('state') }}">
        </div>
        <div>
            <label for="preferred_language">{{ __('messages.nav.language') }}</label>
            <select id="preferred_language" name="preferred_language">
                <option value="en" @selected(old('preferred_language', 'en') === 'en')>English</option>
                <option value="hi" @selected(old('preferred_language') === 'hi')>हिन्दी</option>
            </select>
        </div>
    </div>

    <div class="row mt-3">
        <div>
            <label for="password">{{ __('messages.auth.password') }} <span class="req">*</span></label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   @error('password') aria-invalid="true" @enderror>
            <span class="hint">At least 8 characters. Use a mix of letters and numbers.</span>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="password_confirmation">{{ __('messages.auth.password_confirm') }} <span class="req">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
    </div>

    <div class="checkbox-row mt-3">
        <input type="checkbox" id="terms" name="terms" value="1" required @checked(old('terms')) @error('terms') aria-invalid="true" @enderror>
        <label for="terms">{!! __('messages.auth.terms', ['terms' => route('legal.terms'), 'privacy' => route('legal.privacy')]) !!}</label>
    </div>
    @error('terms')<span class="field-error">{{ $message }}</span>@enderror

    <div class="card-actions">
        <button class="btn block" type="submit">{{ __('messages.auth.register_title') }}</button>
    </div>
</form>

<p class="small mt-3">
    {{ __('messages.auth.have_account') }} <a href="{{ route('login') }}">{{ __('messages.auth.login_title') }}</a>
</p>
@endsection
