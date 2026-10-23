@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="page-head">
    <h1>Users</h1>
    <p class="muted">Role and status changes are recorded in the audit trail. The last active administrator cannot be demoted or suspended.</p>
</div>

@include('admin._nav')

<form method="GET" action="{{ route('admin.users') }}" class="card" role="search">
    <div class="row">
        <div>
            <label for="u-q">Search</label>
            <input id="u-q" name="q" type="search" value="{{ request('q') }}" placeholder="Name or email">
        </div>
        <div>
            <label for="u-role">Role</label>
            <select id="u-role" name="role">
                <option value="">All</option>
                @foreach(['student' => 'Student', 'moderator' => 'Moderator', 'admin' => 'Administrator'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('role') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="u-deletion">Deletion requests</label>
            <select id="u-deletion" name="deletion">
                <option value="">All users</option>
                <option value="1" @selected(request()->boolean('deletion'))>Only pending deletion ({{ $deletionRequests }})</option>
            </select>
        </div>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="{{ route('admin.users') }}">Clear</a>
    </div>
</form>

<div class="tablewrap">
    <table>
        <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Joined</th><th scope="col">Update</th></tr></thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}@if($user->hasPendingDeletion())<br><span class="badge b-unverified">deletion requested</span>@endif</td>
                    <td class="small">{{ $user->email }}</td>
                    <td><span class="badge b-neutral">{{ $user->role }}</span></td>
                    <td>
                        <span class="badge b-{{ $user->account_status === 'active' ? 'officially_verified' : 'unverified' }}">{{ $user->account_status }}</span>
                    </td>
                    <td class="small">{{ $user->created_at?->format('d M Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row tight">
                            @csrf
                            @method('PUT')
                            <select name="role" aria-label="Role for {{ $user->name }}">
                                @foreach(['student' => 'Student', 'moderator' => 'Moderator', 'admin' => 'Administrator'] as $val => $label)
                                    <option value="{{ $val }}" @selected($user->role === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="account_status" aria-label="Status for {{ $user->name }}">
                                <option value="active" @selected($user->account_status === 'active')>Active</option>
                                <option value="suspended" @selected($user->account_status === 'suspended')>Suspended</option>
                            </select>
                            <button class="btn sm" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No users match.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pager">{{ $users->links() }}</div>
@endsection
