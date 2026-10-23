@extends('layouts.app')
@section('title', __('messages.legal.privacy_title'))

@section('content')
<div class="page-head">
    <h1>{{ __('messages.legal.privacy_title') }}</h1>
    <p class="muted">This notice describes exactly what this platform stores and why. It is written to match the application's behaviour, not aspirational policy.</p>
</div>

<section class="section">
    <h2>What we collect</h2>
    <ul>
        <li><strong>Account data:</strong> your name, email address, and optionally a phone number, current city and state, plus your preferred language.</li>
        <li><strong>Your activity:</strong> saved itineraries, saved places, and community posts and replies you create.</li>
        <li><strong>Assistant transcripts:</strong> only for signed-in users, and only for a limited retention window (below).</li>
        <li><strong>Search outcomes:</strong> an anonymised summary of what was searched and how many results were returned, used to find data gaps. It does not contain your identity beyond an optional internal user id.</li>
        <li><strong>Operational telemetry:</strong> for external integrations, only the provider, operation, success flag, HTTP status, latency and a coarse error category. Never API keys, request bodies or personal data.</li>
    </ul>

    <h2>What we do not collect</h2>
    <ul>
        <li>Your precise home address, unless you choose to type it as a starting address for a plan.</li>
        <li>Continuous or background device location. Location sharing is a one-off, consent-based action in your browser.</li>
        <li>Payment details. This platform does not sell tickets and takes no payments.</li>
    </ul>
</section>

<section class="section">
    <h2>Retention</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Data</th><th scope="col">Kept for</th></tr></thead>
            <tbody>
                <tr><td>Assistant chat transcripts</td><td>{{ $retention['chat_days'] }} days</td></tr>
                <tr><td>Anonymised search outcomes</td><td>{{ $retention['search_log_days'] }} days</td></tr>
                <tr><td>Integration health events</td><td>{{ $retention['integration_event_days'] }} days</td></tr>
                <tr><td>Import validation rows</td><td>{{ $retention['import_row_days'] }} days</td></tr>
                <tr><td>Account-deletion grace period</td><td>{{ $retention['deletion_grace_days'] }} days</td></tr>
            </tbody>
        </table>
    </div>
    <p class="small muted">A scheduled maintenance command enforces every window above automatically.</p>
</section>

<section class="section">
    <h2>Deleting your account</h2>
    <p>From your profile you can request deletion, cancel it within the grace period, or delete immediately by confirming your password. Deletion removes your itineraries, saved places and chat history and anonymises your profile.</p>
</section>

<section class="section">
    <h2>Third-party services</h2>
    <p class="small muted">
        If a Google Maps server key is configured, address geocoding and route lookups are sent to Google Maps Platform. If a live places provider is enabled, place queries are sent to that provider and cached only for the short period its terms allow. If an LLM assistant provider is configured, your question may be sent to that provider; the default answer engine is fully local and makes no external call. No integration receives your password or account identity.
    </p>
</section>

<div class="panel">
    <p class="mb-0">Questions about privacy: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
</div>
@endsection
