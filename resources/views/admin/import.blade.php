@extends('layouts.app')
@section('title', 'CSV import')

@section('content')
<div class="page-head">
    <h1>Centre CSV import</h1>
    <p class="muted">Upload a verified centre list. Rows are validated and previewed first; nothing is written until you approve. Imported centres are always marked <strong>unverified</strong> until re-checked.</p>
</div>

@include('admin._nav')

<section class="card">
    <h2 class="mt-0">Upload</h2>
    <form method="POST" action="{{ route('admin.import.upload') }}" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="csv">CSV file <span class="req">*</span></label>
            <input id="csv" name="csv" type="file" accept=".csv,.txt" required>
            <span class="hint">Max 5 MB. Required columns: <span class="mono">{{ implode(', ', $required) }}</span></span>
            @error('csv')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="filters-actions">
            <button class="btn" type="submit">Validate &amp; preview</button>
            <a class="btn ghost" href="{{ route('admin.import.template') }}">Download template</a>
        </div>
    </form>
    <p class="small muted">Optional columns: <span class="mono">{{ implode(', ', $optional) }}</span></p>
</section>

<section class="section">
    <h2>Recent batches</h2>
    <div class="tablewrap">
        <table>
            <thead>
                <tr><th scope="col">File</th><th scope="col">Uploaded by</th><th scope="col">Status</th><th scope="col">Valid</th><th scope="col">Duplicate</th><th scope="col">Error</th><th scope="col">Imported</th><th scope="col"></th></tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td class="small">{{ $batch->file_name }}</td>
                        <td class="small">{{ $batch->user?->name ?? '—' }}</td>
                        <td>
                            <span class="badge b-{{ $batch->status === 'imported' ? 'officially_verified' : ($batch->status === 'rejected' || $batch->status === 'failed' ? 'unverified' : 'estimated') }}">{{ $batch->status }}</span>
                        </td>
                        <td>{{ $batch->valid_rows }}</td>
                        <td>{{ $batch->duplicate_rows }}</td>
                        <td>{{ $batch->error_rows }}</td>
                        <td>{{ $batch->imported_rows }}</td>
                        <td><a class="btn sm ghost" href="{{ route('admin.import.show', $batch) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No imports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
