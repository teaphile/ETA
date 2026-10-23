@extends('layouts.app')
@section('title', 'Reports')

@section('content')
<div class="page-head">
    <h1>Moderation queue</h1>
    <p class="muted">Community reports and reported assistant answers. Every decision is recorded in the audit trail.</p>
</div>

@include('admin._nav')

<section class="section">
    <h2>Pending content reports</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Reported</th><th scope="col">Reason</th><th scope="col">Reporter</th><th scope="col">When</th><th scope="col">Resolve</th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td class="small">
                            <span class="mono tiny">{{ class_basename($report->reportable_type) }}#{{ $report->reportable_id }}</span>
                        </td>
                        <td class="small">{{ $report->reason }}</td>
                        <td class="small">{{ $report->reporter?->name ?? '—' }}</td>
                        <td class="small">{{ $report->created_at?->format('d M Y H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                                @csrf
                                <div class="row tight">
                                    <select name="action" required aria-label="Action">
                                        <option value="dismiss">Dismiss</option>
                                        <option value="hide">Hide content</option>
                                        <option value="remove">Remove content</option>
                                        <option value="warn">Warn only</option>
                                    </select>
                                    <input name="moderator_note" type="text" maxlength="1000" placeholder="Note (optional)">
                                    <button class="btn sm" type="submit">Apply</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No pending reports. Good.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $reports->links() }}</div>
</section>

<section class="section">
    <h2>Reported assistant answers</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Answer</th><th scope="col">Reported by</th><th scope="col">Reason</th><th scope="col">When</th></tr></thead>
            <tbody>
                @forelse($reportedAnswers as $answer)
                    <tr>
                        <td class="small">{{ \Illuminate\Support\Str::limit($answer->content, 160) }}<br><span class="tiny muted">{{ $answer->provider }}</span></td>
                        <td class="small">{{ $answer->conversation?->user?->name ?? '—' }}</td>
                        <td class="small">{{ $answer->report_reason ?? '—' }}</td>
                        <td class="small">{{ $answer->reported_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No reported answers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $reportedAnswers->links() }}</div>
</section>

<section class="section">
    <h2>Recently resolved</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Reported</th><th scope="col">Status</th><th scope="col">Moderator</th><th scope="col">Note</th></tr></thead>
            <tbody>
                @forelse($resolved as $report)
                    <tr>
                        <td class="small mono tiny">{{ class_basename($report->reportable_type) }}#{{ $report->reportable_id }}</td>
                        <td><span class="badge b-neutral">{{ $report->status }}</span></td>
                        <td class="small">{{ $report->moderator?->name ?? '—' }}</td>
                        <td class="small muted">{{ $report->moderator_note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Nothing resolved yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
