@extends('layouts.app')
@section('title', __('messages.legal.rules_title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.legal.rules_title') }}</h1>
    <p class="muted">The community is for practical, honest travel help between aspirants. These rules keep it useful.</p>
</div>

<section class="section">
    <h2>Do</h2>
    <ul>
        <li>Share specific, first-hand travel information: routes, timings, approximate costs, what to expect at a centre.</li>
        <li>Be clear when something is your opinion or experience rather than official fact.</li>
        <li>Help others find the official source (admit card, examination website, booking portal).</li>
    </ul>

    <h2>Do not</h2>
    <ul>
        <li>Post someone's personal data, phone number, exact address or documents.</li>
        <li>Post anything that claims to be official instructions from an examination authority.</li>
        <li>Advertise, spam, scrape, or post misleading prices or availability as fact.</li>
        <li>Harass, discriminate or threaten other users.</li>
    </ul>

    <h2>Moderation</h2>
    <p>Anyone can report a post or reply. Moderators may hide, remove, lock or restore content, and every moderation decision is recorded in an append-only audit trail. Repeated abuse can result in suspension.</p>
</section>

<div class="panel panel-soft">
    <p class="small mb-0">Community answers are other students' experiences — they are not official instructions. Always rely on official sources for examination rules. Contact: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
</div>
@endsection
