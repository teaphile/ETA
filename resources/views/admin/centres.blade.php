@extends('layouts.app')
@section('title', 'Centres')

@section('content')
<div class="page-head">
    <h1>Exam centres</h1>
    <p class="muted">Every centre records its source and verification status. Nothing here is invented — unverified rows are labelled as such.</p>
</div>

@include('admin._nav')

<section class="card">
    <h2 class="mt-0">Add a centre</h2>
    <form method="POST" action="{{ route('admin.centres.store') }}">
        @csrf
        <div class="row">
            <div>
                <label for="c-code">Centre code <span class="req">*</span></label>
                <input id="c-code" name="centre_code" type="text" required maxlength="50" value="{{ old('centre_code') }}">
            </div>
            <div>
                <label for="c-exam">Examination</label>
                <select id="c-exam" name="examination_id">
                    <option value="">—</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}" @selected(old('examination_id') == $exam->id)>{{ $exam->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="c-session">Session</label>
                <select id="c-session" name="exam_session_id">
                    <option value="">—</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected(old('exam_session_id') == $session->id)>{{ $session->examination?->name }} — {{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3">
            <label for="c-name">Name <span class="req">*</span></label>
            <input id="c-name" name="name" type="text" required maxlength="250" value="{{ old('name') }}">
        </div>
        <div class="mt-3">
            <label for="c-address">Address <span class="req">*</span></label>
            <input id="c-address" name="address" type="text" required maxlength="500" value="{{ old('address') }}">
        </div>

        <div class="row mt-3">
            <div>
                <label for="c-city">City <span class="req">*</span></label>
                <input id="c-city" name="city" type="text" required maxlength="100" value="{{ old('city') }}">
            </div>
            <div>
                <label for="c-district">District</label>
                <input id="c-district" name="district" type="text" maxlength="100" value="{{ old('district') }}">
            </div>
            <div>
                <label for="c-state">State <span class="req">*</span></label>
                <input id="c-state" name="state" type="text" required maxlength="100" value="{{ old('state') }}">
            </div>
            <div>
                <label for="c-pin">PIN code</label>
                <input id="c-pin" name="pin_code" type="text" maxlength="10" value="{{ old('pin_code') }}">
            </div>
        </div>

        <div class="row mt-3">
            <div>
                <label for="c-lat">Latitude</label>
                <input id="c-lat" name="latitude" type="text" value="{{ old('latitude') }}" placeholder="optional">
            </div>
            <div>
                <label for="c-lng">Longitude</label>
                <input id="c-lng" name="longitude" type="text" value="{{ old('longitude') }}" placeholder="optional">
            </div>
            <div>
                <label for="c-status">Verification <span class="req">*</span></label>
                <select id="c-status" name="verification_status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('verification_status') === $status)>{{ \App\Integrations\Support\DataStatus::label($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mt-3">
            <div>
                <label for="c-source">Source name</label>
                <input id="c-source" name="source_name" type="text" maxlength="200" value="{{ old('source_name') }}" placeholder="e.g. Official NTA centre list (PDF, dated)">
            </div>
            <div>
                <label for="c-source-url">Source URL</label>
                <input id="c-source-url" name="source_url" type="url" maxlength="500" value="{{ old('source_url') }}">
            </div>
        </div>

        <div class="mt-3">
            <label for="c-limitation">Known limitation / note</label>
            <input id="c-limitation" name="error_or_limitation" type="text" maxlength="500" value="{{ old('error_or_limitation') }}">
        </div>

        <div class="card-actions">
            <button class="btn mt-3" type="submit">Add centre</button>
        </div>
    </form>
</section>

<section class="section">
    <h2>Search &amp; manage</h2>
    <form method="GET" action="{{ route('admin.centres') }}" class="card" role="search">
        <div class="row">
            <div>
                <label for="f-q">Search</label>
                <input id="f-q" name="q" type="search" value="{{ request('q') }}" placeholder="Name, code or city">
            </div>
            <div>
                <label for="f-status">Record status</label>
                <select id="f-status" name="status">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
            </div>
            <div>
                <label for="f-verification">Verification</label>
                <select id="f-verification" name="verification">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('verification') === $status)>{{ \App\Integrations\Support\DataStatus::label($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filters-actions">
            <button class="btn" type="submit">Filter</button>
            <a class="btn ghost" href="{{ route('admin.centres') }}">Clear</a>
        </div>
    </form>

    <div class="tablewrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Code</th><th scope="col">Name</th><th scope="col">City</th>
                    <th scope="col">Examination</th><th scope="col">Verification</th><th scope="col">Freshness</th><th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($centres as $centre)
                    <tr>
                        <td class="mono tiny">{{ $centre->centre_code }}@if($centre->is_demo)<br><span class="badge b-demo">demo</span>@endif</td>
                        <td>{{ $centre->name }}</td>
                        <td class="small">{{ $centre->city }}, {{ $centre->state }}</td>
                        <td class="small">{{ $centre->examination?->name ?? '—' }}@if($centre->session)<br><span class="tiny muted">{{ $centre->session->name }}</span>@endif</td>
                        <td><span class="badge b-{{ $centre->data_status }}">{{ $centre->statusLabel() }}</span></td>
                        <td class="small">{{ $centre->last_verified_at ? $centre->last_verified_at->format('d M Y') : 'never' }}</td>
                        <td>
                            <details class="disclosure">
                                <summary class="tiny">Manage</summary>

                                <form method="POST" action="{{ route('admin.centres.verify', $centre) }}" class="panel mt-2">
                                    @csrf
                                    <strong class="small">Re-verify</strong>
                                    <div class="row tight mt-2">
                                        <select name="verification_status" required>
                                            <option value="officially_verified">Officially verified</option>
                                            <option value="admin_verified">Admin verified</option>
                                            <option value="third_party">Third-party source</option>
                                        </select>
                                        <input name="source_name" type="text" maxlength="200" placeholder="Source name" value="{{ $centre->source_name }}">
                                        <input name="source_url" type="url" maxlength="500" placeholder="Source URL" value="{{ $centre->source_url }}">
                                    </div>
                                    <button class="btn sm mt-2" type="submit">Mark verified</button>
                                </form>

                                <form method="POST" action="{{ route('admin.centres.update', $centre) }}" class="panel mt-2">
                                    @csrf
                                    @method('PUT')
                                    <strong class="small">Edit</strong>
                                    <div class="row tight mt-2">
                                        <input name="centre_code" type="text" required maxlength="50" value="{{ $centre->centre_code }}">
                                        <input name="name" type="text" required maxlength="250" value="{{ $centre->name }}">
                                        <input name="city" type="text" required maxlength="100" value="{{ $centre->city }}">
                                        <input name="state" type="text" required maxlength="100" value="{{ $centre->state }}">
                                    </div>
                                    <input name="address" type="text" required maxlength="500" value="{{ $centre->address }}" placeholder="Address" class="mt-2">
                                    <div class="row tight mt-2">
                                        <input name="latitude" type="text" value="{{ $centre->latitude }}" placeholder="lat">
                                        <input name="longitude" type="text" value="{{ $centre->longitude }}" placeholder="lng">
                                        <select name="verification_status" required>
                                            @foreach($statuses as $status)
                                                <option value="{{ $status }}" @selected($centre->data_status === $status)>{{ \App\Integrations\Support\DataStatus::label($status) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="status" required>
                                            <option value="active" @selected($centre->status === 'active')>Active</option>
                                            <option value="archived" @selected($centre->status === 'archived')>Archived</option>
                                        </select>
                                    </div>
                                    <button class="btn sm mt-2" type="submit">Save changes</button>
                                </form>

                                <form method="POST" action="{{ route('admin.centres.archive', $centre) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn sm danger" type="submit">Archive</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No centres match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $centres->links() }}</div>
</section>
@endsection
