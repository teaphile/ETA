@extends('layouts.app')
@section('title', 'Stale data')

@section('content')
<div class="page-head">
    <h1>Stale data</h1>
    <p class="muted">Centres that have not been re-verified within the {{ $window }}-month freshness window. Re-verify them so students are not shown outdated information.</p>
</div>

@include('admin._nav')

<div class="alert warn">
    A centre with no verification date, or one past its validity window, is treated as stale. Missing verification is never presented as fresh.
</div>

<div class="tablewrap">
    <table>
        <thead><tr><th scope="col">Code</th><th scope="col">Name</th><th scope="col">City</th><th scope="col">Verification</th><th scope="col">Last verified</th><th scope="col"></th></tr></thead>
        <tbody>
            @forelse($centres as $centre)
                <tr>
                    <td class="mono tiny">{{ $centre->centre_code }}</td>
                    <td>{{ $centre->name }}</td>
                    <td class="small">{{ $centre->city }}, {{ $centre->state }}</td>
                    <td><span class="badge b-{{ $centre->data_status }}">{{ \App\Integrations\Support\DataStatus::label($centre->data_status) }}</span></td>
                    <td class="small">{{ $centre->last_verified_at ? $centre->last_verified_at->format('d M Y') : 'never' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.centres.verify', $centre) }}" class="row tight">
                            @csrf
                            <input type="hidden" name="verification_status" value="admin_verified">
                            <input name="source_name" type="text" maxlength="200" placeholder="Source name" value="{{ $centre->source_name }}">
                            <button class="btn sm" type="submit">Re-verify now</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No stale centres. Everything is fresh.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pager">{{ $centres->links() }}</div>
@endsection
