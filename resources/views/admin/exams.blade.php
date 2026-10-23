@extends('layouts.app')
@section('title', 'Examinations')

@section('content')
<div class="page-head">
    <h1>Examinations &amp; sessions</h1>
    <p class="muted">Examinations are reference data. Centres are always imported separately from a verified source.</p>
</div>

@include('admin._nav')

<div class="grid-2">
    <section class="card">
        <h2 class="mt-0">Add examination</h2>
        <form method="POST" action="{{ route('admin.exams.store') }}">
            @csrf
            <div>
                <label for="exam-name">Name <span class="req">*</span></label>
                <input id="exam-name" name="name" type="text" required minlength="2" maxlength="200" value="{{ old('name') }}">
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
                @error('slug')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="mt-3">
                <label for="exam-authority">Conducting authority</label>
                <input id="exam-authority" name="conducting_authority" type="text" maxlength="200" value="{{ old('conducting_authority') }}">
            </div>
            <div class="mt-3">
                <label for="exam-url">Official URL</label>
                <input id="exam-url" name="official_url" type="url" maxlength="500" value="{{ old('official_url') }}">
                @error('official_url')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="mt-3">
                <label for="exam-desc">Description</label>
                <textarea id="exam-desc" name="description" maxlength="2000">{{ old('description') }}</textarea>
            </div>
            <div class="card-actions">
                <button class="btn mt-3" type="submit">Add examination</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="mt-0">Add session</h2>
        @if($exams->isEmpty())
            <p class="muted">Add an examination first.</p>
        @else
        <form method="POST" action="{{ route('admin.sessions.store') }}">
            @csrf
            <div>
                <label for="session-exam">Examination <span class="req">*</span></label>
                <select id="session-exam" name="examination_id" required>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-3">
                <label for="session-name">Session name <span class="req">*</span></label>
                <input id="session-name" name="name" type="text" required maxlength="150" placeholder="e.g. Session 2026">
            </div>
            <div class="row mt-3">
                <div>
                    <label for="session-year">Year</label>
                    <input id="session-year" name="year" type="number" min="2000" max="2100" value="{{ old('year', now()->year) }}">
                </div>
                <div>
                    <label for="session-date">Exam date</label>
                    <input id="session-date" name="exam_date" type="date" value="{{ old('exam_date') }}">
                </div>
                <div>
                    <label for="session-status">Status <span class="req">*</span></label>
                    <select id="session-status" name="status" required>
                        @foreach(['upcoming' => 'Upcoming', 'active' => 'Active', 'completed' => 'Completed', 'archived' => 'Archived'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('status') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn mt-3" type="submit">Add session</button>
            </div>
        </form>
        @endif
    </section>
</div>

<section class="section">
    <h2>Examinations</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Name</th><th scope="col">Authority</th><th scope="col">Centres</th><th scope="col">Official site</th></tr></thead>
            <tbody>
                @forelse($exams as $exam)
                    <tr>
                        <td>{{ $exam->name }}</td>
                        <td class="small muted">{{ $exam->conducting_authority ?? '—' }}</td>
                        <td>{{ number_format($exam->centres_count) }}</td>
                        <td class="small">@if($exam->official_url)<a href="{{ $exam->official_url }}" target="_blank" rel="noopener nofollow">link ↗</a>@else —@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No examinations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $exams->links() }}</div>
</section>

<section class="section">
    <h2>Recent sessions</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Session</th><th scope="col">Examination</th><th scope="col">Year</th><th scope="col">Exam date</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->name }}</td>
                        <td class="small">{{ $session->examination?->name ?? '—' }}</td>
                        <td>{{ $session->year ?? '—' }}</td>
                        <td class="small">{{ $session->exam_date?->format('d M Y') ?? '—' }}</td>
                        <td><span class="badge b-neutral">{{ $session->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No sessions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
