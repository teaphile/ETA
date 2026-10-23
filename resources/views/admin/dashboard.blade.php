@extends('layouts.app')
@section('title', 'Admin dashboard')

@section('content')
<div class="page-head">
    <h1>Admin dashboard</h1>
    <p class="muted">Real figures derived from application data only. No analytics are fabricated or estimated here.</p>
</div>

@include('admin._nav')

<div class="stat-grid">
    <div class="stat"><span class="stat-value">{{ number_format($stats['users']) }}</span><span class="stat-label">Users</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['students']) }}</span><span class="stat-label">Students</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['centres']) }}</span><span class="stat-label">Active centres</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['exams']) }}</span><span class="stat-label">Examinations</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['posts']) }}</span><span class="stat-label">Community posts</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['itineraries']) }}</span><span class="stat-label">Saved trips</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['reports']) }}</span><span class="stat-label">Pending reports</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($reportedAnswers) }}</span><span class="stat-label">Reported answers</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['demo']) }}</span><span class="stat-label">Demo centres</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['stale']) }}</span><span class="stat-label">Stale centres</span></div>
    <div class="stat"><span class="stat-value">{{ number_format($stats['deletion_requests']) }}</span><span class="stat-label">Deletion requests</span></div>
</div>

@if($stats['demo'] > 0)
    <div class="alert warn mt-4">
        <span class="badge b-demo">DEMO DATA</span>
        {{ $stats['demo'] }} demonstration centres are present. Replace them with a verified import before launch.
        <a href="{{ route('admin.import') }}">Import centres →</a>
    </div>
@endif

<section class="section">
    <h2>Search outcomes (last {{ $searchOutcome['window_days'] }} days)</h2>
    <div class="stat-grid">
        <div class="stat"><span class="stat-value">{{ number_format($searchOutcome['total']) }}</span><span class="stat-label">Searches</span></div>
        <div class="stat"><span class="stat-value">{{ number_format($searchOutcome['no_results']) }}</span><span class="stat-label">Zero-result searches</span></div>
        <div class="stat"><span class="stat-value">{{ $searchOutcome['no_result_rate'] }}%</span><span class="stat-label">No-result rate</span></div>
    </div>
    @if($searchesByType->isNotEmpty())
        <div class="tablewrap mt-3">
            <table>
                <thead><tr><th scope="col">Type</th><th scope="col">Searches</th><th scope="col">Empty</th></tr></thead>
                <tbody>
                    @foreach($searchesByType as $row)
                        <tr><td>{{ $row->type }}</td><td>{{ number_format($row->total) }}</td><td>{{ number_format($row->empty) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="section">
    <h2>Integrations</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Integration</th><th scope="col">Provider</th><th scope="col">Status</th><th scope="col">Notes</th></tr></thead>
            <tbody>
                @foreach($integrations as $integration)
                    <tr>
                        <td>{{ $integration['label'] }}</td>
                        <td><span class="mono">{{ $integration['provider'] }}</span></td>
                        <td><span class="badge b-{{ $integration['available'] ? 'officially_verified' : 'unavailable' }}">{{ $integration['available'] ? 'configured' : 'unavailable' }}</span></td>
                        <td class="small muted">{{ $integration['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div class="grid-2">
    <section class="section">
        <h2>Top destinations</h2>
        @if($topDestinations->isEmpty())
            <p class="muted">No saved trips yet.</p>
        @else
            <ul>
                @foreach($topDestinations as $row)
                    <li>{{ $row->destination_city }} — {{ number_format($row->total) }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="section">
        <h2>Centres by city</h2>
        @if($centresByCity->isEmpty())
            <p class="muted">No centres imported yet.</p>
        @else
            <ul>
                @foreach($centresByCity as $row)
                    <li>{{ $row->city }} — {{ number_format($row->total) }}</li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<section class="section">
    <h2>Assistant answer categories</h2>
    @if($assistantCategories->isEmpty())
        <p class="muted">No assistant answers recorded yet.</p>
    @else
        <ul>
            @foreach($assistantCategories as $row)
                <li>{{ $row->category ?: 'uncategorised' }} — {{ number_format($row->total) }}</li>
            @endforeach
        </ul>
    @endif
</section>

<section class="section">
    <h2>Provider errors (last 30 days)</h2>
    @if($providerErrors->isEmpty())
        <p class="muted">No provider failures recorded. Good.</p>
    @else
        <div class="tablewrap">
            <table>
                <thead><tr><th scope="col">Provider</th><th scope="col">Category</th><th scope="col">Count</th></tr></thead>
                <tbody>
                    @foreach($providerErrors as $row)
                        <tr><td class="mono">{{ $row->provider }}</td><td>{{ $row->error_category }}</td><td>{{ number_format($row->total) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="section">
    <h2>Recent audit trail</h2>
    @if($recentAudit->isEmpty())
        <p class="muted">No administrative actions recorded yet.</p>
    @else
        <div class="tablewrap">
            <table>
                <thead><tr><th scope="col">When</th><th scope="col">Actor</th><th scope="col">Action</th><th scope="col">Summary</th></tr></thead>
                <tbody>
                    @foreach($recentAudit as $log)
                        <tr>
                            <td class="small">{{ $log->created_at?->format('d M Y H:i') }}</td>
                            <td class="small">{{ $log->user?->name ?? 'system' }}</td>
                            <td><span class="mono tiny">{{ $log->action }}</span></td>
                            <td class="small muted">{{ $log->summary }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p><a class="btn ghost sm" href="{{ route('admin.audits') }}">Full audit trail →</a></p>
    @endif
</section>
@endsection
