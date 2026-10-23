@extends('layouts.app')
@section('title', $post->title)

@section('content')
<div class="breadcrumb"><a href="{{ route('community.index') }}">{{ __('messages.community.title') }}</a> / {{ \Illuminate\Support\Str::limit($post->title, 60) }}</div>

@if(! $post->isVisible())
    <div class="alert warn">{{ __('messages.community.hidden') }}</div>
@endif

<article class="card">
    <div class="post-meta">
        <span class="avatar" aria-hidden="true">{{ $post->user?->initials() ?? '?' }}</span>
        <span>{{ $post->user?->name ?? 'Student' }}</span>
        <span>· {{ $post->created_at?->diffForHumans() }}</span>
        @if($post->city) <span class="chip">{{ $post->city }}</span> @endif
        <span class="badge b-neutral">{{ config('exam_travel.community_categories.'.$post->category, $post->category) }}</span>
        @if($post->is_locked)<span class="badge b-stale">locked</span>@endif
        @if($post->reports_count > 0)<span class="badge b-unverified">{{ $post->reports_count }} report(s)</span>@endif
    </div>

    <h1 class="mt-3 mb-2">{{ $post->title }}</h1>
    <div class="stack-sm">{!! nl2br(e($post->body)) !!}</div>

    @if($post->examination)
        <p class="small muted mt-3">{{ __('messages.centres.examination') }}: <a href="{{ route('centres.index', ['exam' => $post->examination_id]) }}">{{ $post->examination->name }}</a></p>
    @endif

    <div class="card-actions">
        @auth
            <details class="disclosure">
                <summary>{{ __('messages.community.report') }}</summary>
                <form method="POST" action="{{ route('community.report') }}" class="card">
                    @csrf
                    <input type="hidden" name="target_type" value="post">
                    <input type="hidden" name="target_id" value="{{ $post->id }}">
                    <label for="report-post-reason">{{ __('messages.community.report_reason') }}</label>
                    <textarea id="report-post-reason" name="reason" minlength="5" maxlength="1000" required></textarea>
                    <div class="filters-actions"><button class="btn sm danger" type="submit">{{ __('messages.community.report') }}</button></div>
                </form>
            </details>
        @endauth
    </div>

    @if($isModerator)
        <form method="POST" action="{{ route('community.moderate', $post) }}" class="panel panel-warn mt-3">
            @csrf
            <label for="moderate-action">Moderator action</label>
            <div class="row tight">
                <select id="moderate-action" name="action" required>
                    <option value="hide">Hide</option>
                    <option value="remove">Remove</option>
                    <option value="restore">Restore</option>
                    <option value="lock">Lock</option>
                    <option value="unlock">Unlock</option>
                </select>
                <button class="btn sm danger" type="submit">Apply</button>
            </div>
        </form>
    @endif
</article>

<section class="section">
    <h2>{{ __('messages.community.replies', ['count' => $replies->total()]) }}</h2>

    @forelse($replies as $reply)
        <div class="reply {{ $reply->isVisible() ? '' : 'is-hidden' }}">
            <div class="post-meta">
                <span class="avatar" aria-hidden="true">{{ $reply->user?->initials() ?? '?' }}</span>
                <span>{{ $reply->user?->name ?? 'Student' }}</span>
                <span>· {{ $reply->created_at?->diffForHumans() }}</span>
                @if(! $reply->isVisible()) <span class="badge b-unverified">{{ $reply->status }}</span> @endif
            </div>
            <div class="stack-sm mt-2">{!! nl2br(e($reply->body)) !!}</div>
            @auth
                @if($reply->isVisible())
                    <details class="disclosure">
                        <summary class="tiny">{{ __('messages.community.report') }}</summary>
                        <form method="POST" action="{{ route('community.report') }}">
                            @csrf
                            <input type="hidden" name="target_type" value="reply">
                            <input type="hidden" name="target_id" value="{{ $reply->id }}">
                            <label class="visually-hidden" for="report-reply-{{ $reply->id }}">{{ __('messages.community.report_reason') }}</label>
                            <textarea id="report-reply-{{ $reply->id }}" name="reason" minlength="5" maxlength="1000" required></textarea>
                            <div class="filters-actions"><button class="btn sm danger" type="submit">{{ __('messages.community.report') }}</button></div>
                        </form>
                    </details>
                @endif
            @endauth
        </div>
    @empty
        <p class="muted">{{ __('messages.common.no_information') }}</p>
    @endforelse

    <div class="pager">{{ $replies->links() }}</div>
</section>

<section class="section">
    <h2>{{ __('messages.community.reply') }}</h2>
    @auth
        @if($post->canReceiveReplies())
            <form method="POST" action="{{ route('community.reply', $post) }}" class="card">
                @csrf
                <label class="visually-hidden" for="reply-body">{{ __('messages.community.reply_placeholder') }}</label>
                <textarea id="reply-body" name="body" minlength="3" maxlength="3000" required placeholder="{{ __('messages.community.reply_placeholder') }}"></textarea>
                <div class="filters-actions"><button class="btn amber" type="submit">{{ __('messages.community.reply') }}</button></div>
                <p class="tiny muted mt-2">{{ __('messages.community.intro') }}</p>
            </form>
        @else
            <div class="alert warn">{{ __('messages.community.locked') }}</div>
        @endif
    @else
        <div class="alert info">
            <a href="{{ route('login') }}">{{ __('messages.nav.login') }}</a> — {{ __('messages.community.sign_in_to_post') }}
        </div>
    @endauth
</section>
@endsection
