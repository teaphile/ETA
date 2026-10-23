@extends('layouts.app')
@section('title', __('messages.profile.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.profile.title') }}</h1>
    <p class="muted">Signed in as <strong>{{ $user->email }}</strong>. Email changes are not allowed here because they require re-verification.</p>
</div>

@if($deletionRequested)
    <div class="alert warn">
        <strong>{{ __('messages.profile.deletion_requested_banner') }}</strong>
        <div class="small">Your data will be removed within {{ config('exam_travel.retention.deletion_grace_days', 30) }} days unless you cancel.</div>
        <form method="POST" action="{{ route('profile.deletion.cancel') }}" class="mt-2">
            @csrf
            @method('DELETE')
            <button class="btn sm" type="submit">{{ __('messages.profile.deletion_cancelled') }}</button>
        </form>
    </div>
@endif

<div class="grid-2">
    <section class="card">
        <h2 class="mt-0">{{ __('messages.profile.title') }}</h2>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div>
                <label for="name">{{ __('messages.auth.name') }}</label>
                <input id="name" name="name" type="text" required minlength="2" maxlength="120" value="{{ old('name', $user->name) }}">
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="row mt-3">
                <div>
                    <label for="phone">{{ __('messages.auth.phone') }}</label>
                    <input id="phone" name="phone" type="tel" maxlength="16" value="{{ old('phone', $user->phone) }}">
                    @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label for="current_city">{{ __('messages.auth.current_city') }}</label>
                    <input id="current_city" name="current_city" type="text" maxlength="100" value="{{ old('current_city', $user->current_city) }}">
                </div>
            </div>

            <div class="row mt-3">
                <div>
                    <label for="state">{{ __('messages.common.state') }}</label>
                    <input id="state" name="state" type="text" maxlength="100" value="{{ old('state', $user->state) }}">
                </div>
                <div>
                    <label for="preferred_language">{{ __('messages.nav.language') }}</label>
                    <select id="preferred_language" name="preferred_language" required>
                        <option value="en" @selected(old('preferred_language', $user->preferred_language) === 'en')>English</option>
                        <option value="hi" @selected(old('preferred_language', $user->preferred_language) === 'hi')>हिन्दी</option>
                    </select>
                </div>
            </div>

            <div class="card-actions">
                <button class="btn" type="submit">{{ __('messages.common.save') }}</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="mt-0">{{ __('messages.profile.danger_zone') }}</h2>
        <p class="muted">{{ __('messages.profile.danger_body') }}</p>

        @if(! $canSelfDelete)
            <div class="alert warn">{{ __('messages.profile.admin_cannot_delete') }}</div>
        @elseif(! $deletionRequested)
            <form method="POST" action="{{ route('profile.deletion.request') }}">
                @csrf
                <button class="btn danger" type="submit">{{ __('messages.profile.deletion_requested_banner') }}</button>
            </form>
        @endif

        @if($canSelfDelete)
            <details class="disclosure mt-4">
                <summary>{{ __('messages.profile.danger_zone') }} — {{ __('messages.common.delete') }}</summary>
                <form method="POST" action="{{ route('profile.destroy') }}" class="panel panel-danger">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="delete-password">{{ __('messages.auth.password') }}</label>
                        <input id="delete-password" name="password" type="password" required autocomplete="current-password">
                        @error('password')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="mt-3">
                        <label for="delete-confirm">{{ __('messages.profile.confirmation_label') }}</label>
                        <input id="delete-confirm" name="confirm" type="text" required pattern="DELETE" placeholder="DELETE">
                        <span class="hint">{{ __('messages.profile.confirm_delete_hint') }}</span>
                        @error('confirm')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <button class="btn danger mt-3" type="submit">{{ __('messages.profile.danger_zone') }}</button>
                </form>
            </details>
        @endif
    </section>
</div>
@endsection
