@extends('layouts.app')
@section('title', 'Import review')

@section('content')
<div class="breadcrumb"><a href="{{ route('admin.import') }}">CSV import</a> / {{ $batch->file_name }}</div>
<div class="page-head">
    <h1>Import review</h1>
    <p class="muted">{{ $batch->file_name }} · uploaded by {{ $batch->user?->name ?? '—' }} · status <span class="mono">{{ $batch->status }}</span></p>
</div>

@include('admin._nav')

@foreach($headerErrors as $error)
    <div class="alert err">{{ $error }}</div>
@endforeach

<div class="stat-grid">
    <div class="stat"><span class="stat-value">{{ $batch->total_rows }}</span><span class="stat-label">Total rows</span></div>
    <div class="stat"><span class="stat-value">{{ $batch->valid_rows }}</span><span class="stat-label">Valid</span></div>
    <div class="stat"><span class="stat-value">{{ $batch->duplicate_rows }}</span><span class="stat-label">Duplicate</span></div>
    <div class="stat"><span class="stat-value">{{ $batch->error_rows }}</span><span class="stat-label">Errors</span></div>
    <div class="stat"><span class="stat-value">{{ $batch->imported_rows }}</span><span class="stat-label">Imported</span></div>
    <div class="stat"><span class="stat-value">{{ $batch->updated_rows }}</span><span class="stat-label">Updated</span></div>
</div>

@if($batch->isAwaitingApproval())
    <section class="card mt-4">
        <h2 class="mt-0">Approve or reject</h2>
        <p class="small muted">Approving writes only the {{ $validRows }} valid row(s), inside a single transaction. Imported centres are marked <strong>unverified</strong>.</p>
        <div class="filters-actions">
            <form method="POST" action="{{ route('admin.import.approve', $batch) }}">
                @csrf
                <div class="checkbox-row">
                    <input type="checkbox" id="update_on_duplicate" name="update_on_duplicate" value="1">
                    <label for="update_on_duplicate">Update existing centres when the code already exists</label>
                </div>
                <button class="btn" type="submit">Approve &amp; import valid rows</button>
            </form>
            <form method="POST" action="{{ route('admin.import.reject', $batch) }}">
                @csrf
                <button class="btn danger" type="submit">Reject batch</button>
            </form>
        </div>
    </section>
@endif

<section class="section">
    <h2>Rows</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">#</th><th scope="col">Status</th><th scope="col">Data</th><th scope="col">Error</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="row-{{ $row->status === 'valid' ? 'valid' : ($row->status === 'duplicate' ? 'duplicate' : 'error') }}">
                        <td>{{ $row->row_number }}</td>
                        <td><span class="badge b-{{ $row->status === 'valid' ? 'officially_verified' : ($row->status === 'error' ? 'unverified' : 'estimated') }}">{{ $row->status }}</span></td>
                        <td class="tiny mono">
                            @foreach(($row->data ?? []) as $key => $value)
                                @if(filled($value)){{ $key }}: {{ $value }}<br>@endif
                            @endforeach
                        </td>
                        <td class="small">{{ $row->error_message ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No rows.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $rows->links() }}</div>
</section>
@endsection
