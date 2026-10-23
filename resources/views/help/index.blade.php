@extends('layouts.app')
@section('title', __('messages.nav.help'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.nav.help') }}</h1>
    <p>Approved answers about exam-centre travel, bookings, safety and how this platform handles your data.</p>
</div>

<div class="panel panel-soft">
    <strong>{{ __('messages.assistant.title') }}</strong>
    <p class="small mt-2 mb-2">{{ __('messages.assistant.intro') }}</p>
    <a class="btn sm" href="{{ route('assistant.index') }}">{{ __('messages.nav.assistant') }}</a>
</div>

@if($faqs->isEmpty())
    <div class="empty card">
        <span class="empty-icon" aria-hidden="true">📖</span>
        <h3>{{ __('messages.common.no_information') }}</h3>
        <p>No help articles have been published yet.</p>
    </div>
@else
    @foreach($faqs as $category => $items)
        <section class="section">
            <h2>{{ $category }}</h2>
            <div class="stack">
                @foreach($items as $faq)
                    <details class="disclosure card">
                        <summary>{{ $faq->question }}</summary>
                        <div class="stack-sm mt-2">{!! nl2br(e($faq->answer)) !!}</div>
                        @if($faq->source_url)
                            <p class="small mt-2"><a href="{{ $faq->source_url }}" target="_blank" rel="noopener nofollow">Official source ↗</a></p>
                        @endif
                    </details>
                @endforeach
            </div>
        </section>
    @endforeach
@endif

<div class="panel">
    <h3>Still stuck?</h3>
    <p class="small muted">
        Support: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
        Chat transcripts are kept for {{ $retentionDays }} days.
        Read the <a href="{{ route('legal.privacy') }}">{{ __('messages.legal.privacy_title') }}</a> for how your data is handled.
    </p>
</div>
@endsection
