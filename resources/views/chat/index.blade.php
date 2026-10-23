@extends('layouts.app')
@section('title', __('messages.assistant.title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.assistant.title') }}</h1>
    <p>{{ __('messages.assistant.intro') }}</p>
</div>

<div class="panel panel-soft">
    <span class="badge b-info">{{ __('messages.assistant.engine') }}: {{ $provider }}</span>
    <span class="badge b-{{ $providerAvailable ? 'officially_verified' : 'unavailable' }}">{{ $providerAvailable ? __('messages.assistant.available') : __('messages.assistant.unavailable_badge') }}</span>
    <p class="tiny muted mt-2 mb-0">
        {{ __('messages.assistant.limit_note', ['days' => $retentionDays]) }}
        @guest {{ __('messages.assistant.guest_transcript') }} @endguest
    </p>
</div>

<div class="grid-2">
    <section>
        <div class="chatbox" id="chatbox" aria-live="polite">
            <div class="msg bot">
                {{ __('messages.assistant.greeting') }}
                <div class="msg-meta muted">{{ __('messages.assistant.grounded_meta') }}</div>
            </div>

            @foreach($history as $message)
                @if($message->role === 'user')
                    <div class="msg me">{{ $message->content }}</div>
                @else
                    <div class="msg bot" data-message-id="{{ $message->id }}">
                        {{ $message->content }}
                        <div class="msg-meta muted">
                            {{ $message->provider ?: 'assistant' }}
                            @if($message->limitation) · {{ $message->limitation }} @endif
                        </div>
                        @if(! empty($message->sources))
                            <div class="msg-sources">
                                <strong class="tiny">{{ __('messages.assistant.sources') }}:</strong>
                                @foreach($message->sources as $source)
                                    <a class="tiny" href="{{ $source['url'] ?? '#' }}" target="_blank" rel="noopener nofollow">{{ $source['title'] ?? 'source' }} ↗</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

        <form class="chat-form" id="chat-form" autocomplete="off">
            <label class="visually-hidden" for="chat-input">{{ __('messages.assistant.placeholder') }}</label>
            <input id="chat-input" name="message" type="text" maxlength="{{ (int) config('exam_travel.assistant.max_question_length', 1000) }}"
                   placeholder="{{ __('messages.assistant.placeholder') }}" required>
            <button class="btn amber" type="submit" id="chat-submit">{{ __('messages.assistant.send') }}</button>
        </form>
        <p class="tiny muted" id="chat-status" role="status"></p>
        <noscript><div class="alert warn">The assistant needs JavaScript. You can still use <a href="{{ route('centres.index') }}">Centre search</a> and <a href="{{ route('help.index') }}">Help &amp; FAQ</a>.</div></noscript>
    </section>

    <aside>
        <div class="panel">
            <h3>{{ __('messages.assistant.capabilities_title') }}</h3>
            <ul class="small muted">
                <li>{{ __('messages.assistant.capability_1') }}</li>
                <li>{{ __('messages.assistant.capability_2') }}</li>
                <li>{{ __('messages.assistant.capability_3') }}</li>
            </ul>
        </div>

        @auth
            <div class="panel">
                <h3>{{ __('messages.assistant.history') }}</h3>
                <p class="small muted">{{ __('messages.assistant.transcript_note', ['days' => $retentionDays]) }}</p>
                <form method="POST" action="{{ route('assistant.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn sm danger" type="submit">{{ __('messages.assistant.clear') }}</button>
                </form>
            </div>
        @else
            <div class="panel panel-soft">
                <p class="small mb-2">{{ __('messages.assistant.sign_in_hint') }}</p>
                <a class="btn sm" href="{{ route('login') }}">{{ __('messages.nav.login') }}</a>
            </div>
        @endauth
    </aside>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
    var form = document.getElementById('chat-form');
    var input = document.getElementById('chat-input');
    var box = document.getElementById('chatbox');
    var status = document.getElementById('chat-status');
    var submit = document.getElementById('chat-submit');
    var askUrl = @json(route('assistant.ask'));
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var authCheck = @json(auth()->check());
    if (!form || !box) return;

    function bubble(html, cls) {
        var el = document.createElement('div');
        el.className = 'msg ' + cls;
        el.innerHTML = html;
        box.appendChild(el);
        box.scrollTop = box.scrollHeight;
        return el;
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (text.length < 2) return;

        bubble(esc(text), 'me');
        input.value = '';
        submit.disabled = true;
        status.textContent = @json(__('messages.common.thinking'));
        var pending = bubble(@json(__('messages.common.thinking')), 'bot pending');

        fetch(askUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: text })
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (data) {
            var html = esc(data.answer || @json(__('messages.assistant.unavailable')));
            html += '<div class="msg-meta muted">' + esc(data.provider || 'assistant');
            if (data.grounded) html += ' · ' + @json(__('messages.assistant.grounded_tag'));
            if (data.limitation) html += ' · ' + esc(data.limitation);
            html += '</div>';
            if (data.sources && data.sources.length) {
                html += '<div class="msg-sources"><strong class="tiny">@json(__('messages.assistant.sources')):</strong> ';
                data.sources.forEach(function (s) {
                    html += '<a class="tiny" href="' + esc(s.url || '#') + '" target="_blank" rel="noopener nofollow">' + esc(s.title || 'source') + ' ↗</a> ';
                });
                html += '</div>';
            }
            if (authCheck && data.message_id) {
                html += '<form method="POST" action="' + @json(route('assistant.report')) + '" class="inline">'
                     + '<input type="hidden" name="_token" value="' + csrf + '">'
                     + '<input type="hidden" name="message_id" value="' + esc(data.message_id) + '">'
                     + '<button class="btn sm ghost" type="submit">@json(__('messages.assistant.report'))</button></form>';
            }
            pending.classList.remove('pending');
            pending.innerHTML = html;
            box.scrollTop = box.scrollHeight;
        }).catch(function () {
            pending.classList.remove('pending');
            pending.innerHTML = esc(@json(__('messages.assistant.unavailable')));
        }).finally(function () {
            submit.disabled = false;
            status.textContent = '';
            input.focus();
        });
    });
})();
</script>
@endpush
