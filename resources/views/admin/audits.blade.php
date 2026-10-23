@extends('layouts.app')
@section('title', 'Audit trail')

@section('content')
<div class="page-head">
    <h1>Audit trail</h1>
    <p class="muted">Append-only record of sensitive administrative actions. Summaries deliberately exclude passwords, tokens and full personal payloads.</p>
</div>

@include('admin._nav')

<form method="GET" action="{{ route('admin.audits') }}" class="card" role="search">
    <div class="row">
        <div>
            <label for="a-action">Action contains</label>
            <input id="a-action" name="action" type="search" value="{{ request('action') }}" placeholder="e.g. centre_reverified">
        </div>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="{{ route('admin.audits') }}">Clear</a>
    </div>
</form>

<div class="tablewrap">
    <table>
        <thead><tr><th scope="col">When</th><th scope="col">Actor</th><th scope="col">Action</th><th scope="col">Entity</th><th scope="col">Summary</th><th scope="col">IP</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="small">{{ $log->created_at?->format('d M Y H:i') }}</td>
                    <td class="small">{{ $log->user?->name ?? 'system' }}</td>
                    <td><span class="mono tiny">{{ $log->action }}</span></td>
                    <td class="small muted">{{ $log->entity_type ? $log->entity_type.'#'.$log->entity_id : '—' }}</td>
                    <td class="small">{{ $log->summary ?? '—' }}</td>
                    <td class="tiny muted">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No audit entries.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pager">{{ $logs->links() }}</div>
@endsection
