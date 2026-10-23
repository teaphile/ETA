@extends('layouts.app')
@section('title', 'Integration health')

@section('content')
<div class="page-head">
    <h1>Integration health</h1>
    <p class="muted">Configuration status and non-sensitive telemetry for every external integration. No API keys or request bodies are shown here.</p>
</div>

@include('admin._nav')

<section class="section">
    <h2>Configured integrations</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Integration</th><th scope="col">Provider</th><th scope="col">Available</th><th scope="col">Notes</th></tr></thead>
            <tbody>
                @foreach($integrations as $integration)
                    <tr>
                        <td>{{ $integration['label'] }}</td>
                        <td><span class="mono">{{ $integration['provider'] }}</span></td>
                        <td>
                            <span class="badge b-{{ $integration['available'] ? 'officially_verified' : 'unavailable' }}">{{ $integration['available'] ? 'yes' : 'no' }}</span>
                        </td>
                        <td class="small muted">{{ $integration['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="small muted">
        Configure credentials in <code>.env</code> — see <code>docs/API_SETUP.md</code>. Every integration degrades to an honest empty state when unconfigured.
    </p>
</section>

<section class="section">
    <h2>Request summary (last 30 days)</h2>
    @if($summary->isEmpty())
        <p class="muted">No external requests recorded in this window. The default providers (curated places, distance-based transport estimates, grounded assistant) make no external calls at all.</p>
    @else
        <div class="tablewrap">
            <table>
                <thead><tr><th scope="col">Provider</th><th scope="col">Operation</th><th scope="col">Calls</th><th scope="col">OK</th><th scope="col">Avg latency (ms)</th></tr></thead>
                <tbody>
                    @foreach($summary as $row)
                        <tr>
                            <td class="mono">{{ $row->provider }}</td>
                            <td>{{ $row->operation }}</td>
                            <td>{{ number_format($row->calls) }}</td>
                            <td>{{ number_format($row->ok_calls) }}</td>
                            <td>{{ $row->avg_latency_ms !== null ? number_format($row->avg_latency_ms, 0) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="section">
    <h2>Recent failures</h2>
    @if($recentFailures->isEmpty())
        <p class="muted">No failures recorded.</p>
    @else
        <div class="tablewrap">
            <table>
                <thead><tr><th scope="col">When</th><th scope="col">Provider</th><th scope="col">Operation</th><th scope="col">HTTP</th><th scope="col">Category</th></tr></thead>
                <tbody>
                    @foreach($recentFailures as $row)
                        <tr>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td>
                            <td class="mono">{{ $row->provider }}</td>
                            <td>{{ $row->operation }}</td>
                            <td>{{ $row->http_status ?? '—' }}</td>
                            <td>{{ $row->error_category ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
