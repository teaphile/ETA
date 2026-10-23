@php
    $brandName = config('exam_travel.brand.name');
    $currentLocale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', __('messages.app.tagline'))">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <title>@yield('title', __('messages.app.name')) — {{ $brandName }}</title>
    {{-- Zero-build stylesheet: no bundler, no external font/CDN request. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
<a class="skip-link" href="#main">{{ __('messages.nav.skip_to_content') }}</a>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark" aria-hidden="true">🎓</span>
            <span>Exam<em>Travel</em>Assist</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav"
                data-nav-toggle>
            <span aria-hidden="true">☰</span> <span>{{ __('messages.nav.toggle_menu') }}</span>
        </button>

        <nav class="nav" id="primary-nav" aria-label="{{ __('messages.nav.primary') }}" data-nav>
            <a href="{{ route('centres.index') }}" @if(request()->routeIs('centres.*')) aria-current="page" @endif>{{ __('messages.nav.centres') }}</a>
            <a href="{{ route('journey.index') }}" @if(request()->routeIs('journey.*')) aria-current="page" @endif>{{ __('messages.nav.journey') }}</a>
            <a href="{{ route('places.index') }}" @if(request()->routeIs('places.*')) aria-current="page" @endif>{{ __('messages.nav.places') }}</a>
            <a href="{{ route('community.index') }}" @if(request()->routeIs('community.*')) aria-current="page" @endif>{{ __('messages.nav.community') }}</a>
            <a href="{{ route('assistant.index') }}" @if(request()->routeIs('assistant.*')) aria-current="page" @endif>{{ __('messages.nav.assistant') }}</a>
            <a href="{{ route('help.index') }}" @if(request()->routeIs('help.*') || request()->routeIs('legal.*')) aria-current="page" @endif>{{ __('messages.nav.help') }}</a>

            @auth
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>{{ __('messages.nav.dashboard') }}</a>
                <a href="{{ route('itineraries.index') }}" @if(request()->routeIs('itineraries.*')) aria-current="page" @endif>{{ __('messages.nav.trips') }}</a>
                @if(auth()->user()->isModerator())
                    <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.*')) aria-current="page" @endif>{{ __('messages.nav.admin') }}</a>
                @endif
                <a href="{{ route('profile') }}" @if(request()->routeIs('profile')) aria-current="page" @endif>{{ __('messages.nav.profile') }}</a>
                <form class="nav-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn sm ghost" type="submit">{{ __('messages.nav.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>{{ __('messages.nav.login') }}</a>
                <a href="{{ route('register') }}" class="btn sm amber" @if(request()->routeIs('register')) aria-current="page" @endif>{{ __('messages.nav.register') }}</a>
            @endauth

            <span class="locale-form" role="group" aria-label="{{ __('messages.nav.language') }}">
                @foreach(['en' => 'EN', 'hi' => 'हिं'] as $code => $label)
                    <form method="POST" action="{{ route('locale.update', $code) }}">
                        @csrf
                        <button type="submit" @if($currentLocale === $code) aria-pressed="true" @else aria-pressed="false" @endif
                                title="{{ $code === 'hi' ? 'हिन्दी' : 'English' }}">{{ $label }}</button>
                    </form>
                @endforeach
            </span>
        </nav>
    </div>
</header>

<main id="main" class="wrap">
    @if(session('success'))
        <div class="alert success" role="status">{{ session('success') }}</div>
    @endif
    @if(session('status'))
        <div class="alert info" role="status">{{ session('status') }}</div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="alert err" role="alert">
            <strong>{{ $errors->count() === 1 ? __('messages.errors.fix_one') : __('messages.errors.fix_many') }}</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer>
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <strong>{{ $brandName }}</strong>
                <p>{{ config('exam_travel.brand.disclaimer') }}</p>
                <p>{{ __('messages.footer.verify') }}</p>
            </div>
            <div>
                <strong>{{ __('messages.footer.explore') }}</strong>
                <p>
                    <a href="{{ route('centres.index') }}">{{ __('messages.nav.centres') }}</a><br>
                    <a href="{{ route('journey.index') }}">{{ __('messages.nav.journey') }}</a><br>
                    <a href="{{ route('places.index') }}">{{ __('messages.nav.places') }}</a><br>
                    <a href="{{ route('assistant.index') }}">{{ __('messages.nav.assistant') }}</a>
                </p>
            </div>
            <div>
                <strong>{{ __('messages.footer.trust') }}</strong>
                <p>
                    <a href="{{ route('help.index') }}">{{ __('messages.nav.help') }}</a><br>
                    <a href="{{ route('legal.privacy') }}">{{ __('messages.legal.privacy_title') }}</a><br>
                    <a href="{{ route('legal.terms') }}">{{ __('messages.legal.terms_title') }}</a><br>
                    <a href="{{ route('legal.community-rules') }}">{{ __('messages.legal.rules_title') }}</a>
                </p>
            </div>
        </div>
        <div class="foot-legal">
            {{ __('messages.footer.estimates') }} · {{ __('messages.footer.ist') }} · © {{ date('Y') }} {{ $brandName }}
        </div>
    </div>
</footer>

@stack('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // Progressive enhancement only. No framework, no external request, CSP-safe.
    (function () {
        'use strict';

        // Mobile navigation.
        var toggle = document.querySelector('[data-nav-toggle]');
        var nav = document.querySelector('[data-nav]');

        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                var open = nav.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            // Close when a link inside is followed (better on phones).
            nav.addEventListener('click', function (e) {
                if (e.target.closest('a')) {
                    nav.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            // Close on Escape and return focus to the toggle.
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                    nav.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
        }

        // Any element with data-print triggers the browser print dialog
        // (kept here so no inline onclick attribute is needed under our CSP).
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-print]');
            if (trigger) {
                e.preventDefault();
                window.print();
            }
        });
    })();
</script>
@stack('body-end')
</body>
</html>
