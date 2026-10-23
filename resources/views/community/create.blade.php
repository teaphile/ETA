@extends('layouts.app')
@section('title', __('messages.community.new_post'))

@section('content')
<div class="breadcrumb"><a href="{{ route('community.index') }}">{{ __('messages.community.title') }}</a> / {{ __('messages.community.new_post') }}</div>
<h1>{{ __('messages.community.new_post') }}</h1>
<p class="muted">{{ __('messages.community.intro') }}</p>

<form method="POST" action="{{ route('community.store') }}" class="card">
    @csrf

    <div>
        <label for="post-title">{{ __('messages.community.post_title') }} <span class="req">*</span></label>
        <input id="post-title" name="title" type="text" minlength="6" maxlength="180" required value="{{ old('title') }}">
    </div>

    <div class="mt-3">
        <label for="post-body">{{ __('messages.community.post_body') }} <span class="req">*</span></label>
        <textarea id="post-body" name="body" minlength="10" maxlength="5000" required>{{ old('body') }}</textarea>
        <span class="hint">Share practical detail: city, budget, what worked. Do not post phone numbers or personal details.</span>
    </div>

    <div class="row mt-3">
        <div>
            <label for="post-city">{{ __('messages.community.post_city') }}</label>
            <input id="post-city" name="city" type="text" maxlength="100" value="{{ old('city', auth()->user()->current_city) }}">
        </div>
        <div>
            <label for="post-category">{{ __('messages.community.post_category') }} <span class="req">*</span></label>
            <select id="post-category" name="category" required>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="post-exam">{{ __('messages.centres.examination') }} <span class="hint">{{ __('messages.common.optional') }}</span></label>
            <select id="post-exam" name="examination_id">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($examinations as $exam)
                    <option value="{{ $exam->id }}" @selected(old('examination_id') == $exam->id)>{{ $exam->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="filters-actions">
        <button class="btn amber" type="submit">{{ __('messages.community.publish') }}</button>
        <a class="btn ghost" href="{{ route('community.index') }}">{{ __('messages.common.cancel') }}</a>
    </div>
    <p class="tiny muted mt-3">By posting you agree to the <a href="{{ route('legal.community-rules') }}">{{ __('messages.community.rules') }}</a>.</p>
</form>
@endsection
