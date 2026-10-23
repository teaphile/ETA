@extends('layouts.app')
@section('title', __('messages.community.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.community.title') }}</h1>
    <p>{{ __('messages.community.intro') }}</p>
</div>

<div class="filters-actions mb-4">
    @auth
        <a class="btn amber" href="{{ route('community.create') }}">{{ __('messages.community.new_post') }}</a>
    @else
        <a class="btn amber" href="{{ route('login') }}">{{ __('messages.nav.login') }} → {{ __('messages.community.new_post') }}</a>
    @endauth
    <a class="btn ghost" href="{{ route('legal.community-rules') }}">{{ __('messages.community.rules') }}</a>
</div>

<form method="GET" action="{{ route('community.index') }}" class="card" role="search">
    <div class="row">
        <div>
            <label for="c-city">{{ __('messages.common.city') }}</label>
            <input id="c-city" name="city" type="text" value="{{ request('city') }}" placeholder="e.g. Patna">
        </div>
        <div>
            <label for="c-exam">{{ __('messages.centres.examination') }}</label>
            <select id="c-exam" name="exam">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($examinations as $exam)
                    <option value="{{ $exam->id }}" @selected(request('exam') == $exam->id)>{{ $exam->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="c-category">{{ __('messages.community.post_category') }}</label>
            <select id="c-category" name="category">
                <option value="">{{ __('messages.common.all') }}</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="c-q">{{ __('messages.common.search') }}</label>
            <input id="c-q" name="q" type="search" value="{{ request('q') }}" placeholder="Title or body">
        </div>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit">{{ __('messages.common.search') }}</button>
        <a class="btn ghost" href="{{ route('community.index') }}">{{ __('messages.common.clear_filters') }}</a>
    </div>
</form>

@if($posts->isEmpty())
    <div class="empty card mt-4">
        <span class="empty-icon" aria-hidden="true">💬</span>
        <h3>{{ __('messages.community.title') }}</h3>
        <p>{{ __('messages.community.intro') }}</p>
        @auth
            <a class="btn" href="{{ route('community.create') }}">{{ __('messages.community.new_post') }}</a>
        @endauth
    </div>
@else
    <div class="stack">
        @foreach($posts as $post)
            <article class="card">
                <div class="post-meta">
                    <span class="avatar" aria-hidden="true">{{ $post->user?->initials() ?? '?' }}</span>
                    <span>{{ $post->user?->name ?? 'Student' }}</span>
                    <span>· {{ $post->created_at?->diffForHumans() }}</span>
                    @if($post->city) <span class="chip">{{ $post->city }}</span> @endif
                    <span class="badge b-neutral">{{ $categories[$post->category] ?? $post->category }}</span>
                    @if($post->is_locked)<span class="badge b-stale">lock</span>@endif
                </div>
                <h3 class="mt-3 mb-2"><a href="{{ route('community.show', $post) }}">{{ $post->title }}</a></h3>
                <p class="small muted">{{ \Illuminate\Support\Str::limit($post->body, 200) }}</p>
                <div class="post-meta">
                    <span>{{ __('messages.community.replies', ['count' => $post->replies_count]) }}</span>
                    @if($post->examination) <span>· {{ $post->examination->name }}</span> @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="pager">{{ $posts->links() }}</div>
@endif

<div class="alert warn mt-5">{{ __('messages.community.intro') }}</div>
@endsection
