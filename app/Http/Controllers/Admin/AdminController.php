<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CentreDataImporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Requests\Admin\StoreCentreRequest;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\StorePlaceRequest;
use App\Http\Requests\Admin\StoreSessionRequest;
use App\Http\Requests\Admin\UpdateCentreRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Integrations\Import\CsvCentreDataImporter;
use App\Integrations\Support\DataStatus;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\CommunityPost;
use App\Models\ContentReport;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\ExamSession;
use App\Models\Faq;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\LocalService;
use App\Models\User;
use App\Services\IntegrationHealthService;
use App\Services\SearchLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Administrative surface.
 *
 * Every state-changing action is authorised on the server (route middleware plus the
 * form-request `authorize()`), recorded in the audit trail, and validated through a
 * form request. Analytics here are derived from real application data only.
 */
class AdminController extends Controller
{
    public function dashboard(SearchLogger $searchLogger, IntegrationHealthService $health)
    {
        $staleCentres = ExamCentre::query()->active()
            ->where(fn ($q) => $q->whereNull('last_verified_at')
                ->orWhere('last_verified_at', '<', now()->subMonths((int) config('exam_travel.freshness.centre_months', 6))))
            ->count();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'students' => User::where('role', User::ROLE_STUDENT)->count(),
                'centres' => ExamCentre::where('status', 'active')->count(),
                'exams' => Examination::count(),
                'posts' => CommunityPost::count(),
                'reports' => ContentReport::where('status', 'pending')->count(),
                'itineraries' => DB::table('itineraries')->count(),
                'demo' => ExamCentre::where('is_demo', true)->count(),
                'stale' => $staleCentres,
                'deletion_requests' => User::whereNotNull('deletion_requested_at')->count(),
            ],
            'searchOutcome' => $searchLogger->outcomeSummary(30),
            'searchesByType' => $searchLogger->byType(30),
            'topDestinations' => DB::table('itineraries')->select('destination_city', DB::raw('count(*) as total'))
                ->groupBy('destination_city')->orderByDesc('total')->limit(8)->get(),
            'centresByCity' => ExamCentre::select('city', DB::raw('count(*) as total'))
                ->where('status', 'active')->groupBy('city')->orderByDesc('total')->limit(8)->get(),
            'assistantCategories' => DB::table('chat_messages')->where('role', 'assistant')
                ->whereNotNull('category')->select('category', DB::raw('count(*) as total'))
                ->groupBy('category')->orderByDesc('total')->limit(8)->get(),
            'providerErrors' => DB::table('integration_events')->where('success', false)
                ->where('created_at', '>=', now()->subDays(30))
                ->select('provider', 'error_category', DB::raw('count(*) as total'))
                ->groupBy('provider', 'error_category')->orderByDesc('total')->limit(10)->get(),
            'recentAudit' => AuditLog::with('user:id,name')->latest('id')->limit(10)->get(),
            'integrations' => $health->configuration(),
            'reportedAnswers' => ChatMessage::reported()->count(),
        ]);
    }

    public function health(IntegrationHealthService $health)
    {
        return view('admin.health', [
            'integrations' => $health->configuration(),
            'summary' => $health->summary(30),
            'recentFailures' => DB::table('integration_events')->where('success', false)
                ->latest('id')->limit(25)->get(),
        ]);
    }

    // --------------------------------------------------------------- examinations

    public function exams()
    {
        return view('admin.exams', [
            'exams' => Examination::withCount('centres')->orderBy('name')->paginate(20),
            'sessions' => ExamSession::with('examination:id,name')->latest('id')->limit(30)->get(),
        ]);
    }

    public function storeExam(StoreExamRequest $request)
    {
        $exam = Examination::create($request->validated() + ['status' => 'active']);
        AuditLog::record('exam_created', Examination::class, $exam->id, $exam->name);

        return back()->with('success', 'Examination added.');
    }

    public function storeSession(StoreSessionRequest $request)
    {
        $session = ExamSession::create($request->validated());
        AuditLog::record('session_created', ExamSession::class, $session->id, $session->name);

        return back()->with('success', 'Session added.');
    }

    // -------------------------------------------------------------------- centres

    public function centres(Request $request)
    {
        $term = $request->string('q')->trim()->toString();

        $centres = ExamCentre::query()
            ->with(['examination:id,name', 'session:id,name'])
            ->when($term !== '', function ($q) use ($term) {
                // Grouped OR so a future AND-condition cannot leak past the search.
                $like = '%'.mb_strtolower($term).'%';
                $q->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(centre_code) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(city) LIKE ?', [$like]);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('verification'), fn ($q) => $q->where('data_status', $request->input('verification')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.centres', [
            'centres' => $centres,
            'exams' => Examination::orderBy('name')->get(),
            'sessions' => ExamSession::with('examination:id,name')->get(),
            'statuses' => DataStatus::values(),
        ]);
    }

    public function storeCentre(StoreCentreRequest $request)
    {
        $data = $request->validated();
        $data['data_status'] = $data['verification_status'];
        $data['created_by'] = $request->user()->id;
        $data['is_demo'] = false;

        if (DataStatus::isVerified($data['verification_status'])) {
            $data['last_verified_at'] = now();
        }

        // Uniqueness is enforced by the database; surface it as a friendly error.
        $exists = ExamCentre::whereRaw('LOWER(centre_code) = ?', [mb_strtolower($data['centre_code'])])
            ->where('examination_id', $data['examination_id'] ?? null)
            ->where('exam_session_id', $data['exam_session_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'centre_code' => 'A centre with this code already exists for that examination/session. Edit the existing record instead.',
            ]);
        }

        $centre = ExamCentre::create($data);
        AuditLog::record('centre_created', ExamCentre::class, $centre->id, $centre->centre_code.' '.$centre->name);

        return back()->with('success', 'Centre added and it now appears in search.');
    }

    public function updateCentre(UpdateCentreRequest $request, ExamCentre $centre)
    {
        $data = $request->validated();
        $data['data_status'] = $data['verification_status'];

        // A change of verification status is a re-verification event: stamp it.
        if (DataStatus::isVerified($data['verification_status'])
            && $centre->verification_status !== $data['verification_status']) {
            $data['last_verified_at'] = now();
        }

        $centre->update($data);
        AuditLog::record('centre_updated', ExamCentre::class, $centre->id, $centre->centre_code);

        return back()->with('success', 'Centre updated.');
    }

    /** Re-verification: an administrator confirms the record is still correct today. */
    public function verifyCentre(Request $request, ExamCentre $centre)
    {
        $data = $request->validate([
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'verification_status' => ['required', 'in:officially_verified,admin_verified,third_party'],
        ]);

        $centre->update([
            'verification_status' => $data['verification_status'],
            'data_status' => $data['verification_status'],
            'source_name' => $data['source_name'] ?? $centre->source_name,
            'source_url' => $data['source_url'] ?? $centre->source_url,
            'last_verified_at' => now(),
            'valid_until' => null,
        ]);

        AuditLog::record('centre_reverified', ExamCentre::class, $centre->id, $data['verification_status']);

        return back()->with('success', 'Centre re-verified and marked fresh.');
    }

    public function destroyCentre(Request $request, ExamCentre $centre)
    {
        $centre->update(['status' => 'archived']);
        AuditLog::record('centre_archived', ExamCentre::class, $centre->id, $centre->centre_code);

        return back()->with('success', 'Centre archived (not deleted) so history is preserved.');
    }

    // --------------------------------------------------------------- CSV imports

    public function importForm()
    {
        return view('admin.import', [
            'batches' => ImportBatch::with('user:id,name')->latest('id')->limit(10)->get(),
            'exams' => Examination::orderBy('name')->get(),
            'required' => CsvCentreDataImporter::REQUIRED_COLUMNS,
            'optional' => CsvCentreDataImporter::OPTIONAL_COLUMNS,
        ]);
    }

    public function importTemplate()
    {
        $headers = array_merge(
            CsvCentreDataImporter::REQUIRED_COLUMNS,
            CsvCentreDataImporter::OPTIONAL_COLUMNS,
        );

        $example = [
            'DEMO-001', 'Example College', 'Main Road', 'Delhi', 'Delhi',
            '110001', '28.61', '77.21', 'JEE Main', '', 'Session 2026', '',
            'Admit-card sample (replace with your verified source)', '', 'active',
        ];

        $csv = implode(',', $headers)."\n"
            .implode(',', array_map(fn ($v) => str_contains($v, ',') ? '"'.$v.'"' : $v, $example))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="centre-import-template.csv"',
        ]);
    }

    public function importUpload(Request $request, CentreDataImporter $importer)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'csv.mimes' => 'Upload a .csv or .txt file exported from a spreadsheet.',
            'csv.max' => 'The file must be 5 MB or smaller. Split larger datasets into batches.',
        ]);

        $file = $request->file('csv');
        $path = $file->store('imports', 'local');

        try {
            $preview = $importer->preview(Storage::disk('local')->path($path));
        } finally {
            Storage::disk('local')->delete($path);
        }

        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'status' => $preview->isRejected() ? ImportBatch::STATUS_REJECTED : ImportBatch::STATUS_AWAITING_APPROVAL,
            'total_rows' => $preview->totalRows,
            'valid_rows' => $preview->validRows,
            'error_rows' => $preview->errorRows,
            'duplicate_rows' => $preview->duplicateRows,
            'notes' => $preview->isRejected() ? implode(' ', $preview->headerErrors) : null,
        ]);

        foreach ($preview->rows as $row) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'row_number' => $row['row_number'],
                'data' => $row['data'],
                'status' => $row['status'],
                'error_message' => $row['errors'] === [] ? null : implode(' ', $row['errors']),
            ]);
        }

        AuditLog::record(
            'import_uploaded',
            ImportBatch::class,
            $batch->id,
            $preview->summary().($preview->isRejected() ? ' — rejected: '.implode(' ', $preview->headerErrors) : ''),
        );

        if ($preview->isRejected()) {
            return redirect()->route('admin.import.show', $batch)
                ->withErrors(['csv' => implode(' ', $preview->headerErrors)]);
        }

        return redirect()->route('admin.import.show', $batch)
            ->with('success', 'Validation finished: '.$preview->summary().'. Review the rows below, then approve to import the valid rows only.');
    }

    public function importShow(ImportBatch $batch)
    {
        return view('admin.import-show', [
            'batch' => $batch->load('user:id,name'),
            'rows' => $batch->rows()->orderBy('row_number')->paginate(30),
            'validRows' => $batch->rows()->where('status', 'valid')->count(),
            'headerErrors' => $batch->notes ? [$batch->notes] : [],
        ]);
    }

    public function importApprove(Request $request, ImportBatch $batch, CentreDataImporter $importer)
    {
        abort_unless($batch->isAwaitingApproval(), 422, 'This batch is not awaiting approval.');

        $data = $request->validate([
            'update_on_duplicate' => ['nullable', 'boolean'],
        ]);

        $rows = $batch->rows()->where('status', 'valid')->orderBy('row_number')->get()
            ->map(fn (ImportRow $r) => ['row_number' => $r->row_number, 'data' => $r->data])
            ->all();

        try {
            $result = $importer->import($rows, [
                'user_id' => $request->user()->id,
                'import_batch_id' => $batch->id,
                'source_name' => 'CSV import: '.$batch->file_name,
                'update_on_duplicate' => $request->boolean('update_on_duplicate'),
            ]);
        } catch (\Throwable $e) {
            $batch->update(['status' => ImportBatch::STATUS_FAILED, 'notes' => 'Import failed: '.$e->getMessage()]);
            AuditLog::record('import_failed', ImportBatch::class, $batch->id, $e->getMessage());

            return back()->withErrors(['csv' => 'The import was rolled back and nothing was written: '.$e->getMessage()]);
        }

        $batch->update([
            'status' => ImportBatch::STATUS_IMPORTED,
            'imported_rows' => $result['imported'],
            'updated_rows' => $result['updated'],
        ]);

        AuditLog::record(
            'import_approved',
            ImportBatch::class,
            $batch->id,
            sprintf('imported=%d updated=%d skipped=%d', $result['imported'], $result['updated'], $result['skipped']),
        );

        return redirect()->route('admin.import.show', $batch)->with(
            'success',
            sprintf(
                '%d centre(s) imported and %d updated. Imported rows are marked "%s" — verify each against an official source before marking it verified.',
                $result['imported'],
                $result['updated'],
                DataStatus::label(DataStatus::Unverified->value),
            ),
        );
    }

    public function importReject(Request $request, ImportBatch $batch)
    {
        abort_unless($batch->isAwaitingApproval(), 422);

        $batch->update(['status' => ImportBatch::STATUS_REJECTED]);
        AuditLog::record('import_rejected', ImportBatch::class, $batch->id, 'rejected by administrator');

        return redirect()->route('admin.import')->with('success', 'Batch rejected. No centres were written.');
    }

    // ---------------------------------------------------------------------- users

    public function users(Request $request)
    {
        $term = $request->string('q')->trim()->toString();

        $users = User::query()
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.mb_strtolower($term).'%';
                $q->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->boolean('deletion'), fn ($q) => $q->whereNotNull('deletion_requested_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'deletionRequests' => User::whereNotNull('deletion_requested_at')->count(),
        ]);
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if ($user->id === $request->user()->id) {
            if (($data['account_status'] ?? null) === 'suspended') {
                return back()->withErrors(['account_status' => 'You cannot suspend your own account.']);
            }
            if (isset($data['role']) && $data['role'] !== User::ROLE_ADMIN) {
                return back()->withErrors(['role' => 'You cannot remove your own administrator role. Ask another administrator.']);
            }
        }

        // Prevent the last administrator from being demoted or suspended.
        if ($user->isAdmin() && (
            (isset($data['role']) && $data['role'] !== User::ROLE_ADMIN)
            || (($data['account_status'] ?? null) === 'suspended')
        )) {
            $remainingAdmins = User::where('role', User::ROLE_ADMIN)
                ->where('id', '!=', $user->id)
                ->where('account_status', 'active')
                ->count();

            if ($remainingAdmins === 0) {
                return back()->withErrors(['role' => 'This is the last active administrator. Promote another account first.']);
            }
        }

        $before = ['role' => $user->role, 'account_status' => $user->account_status];
        $user->update($data);

        AuditLog::record('user_updated', User::class, $user->id, json_encode([
            'before' => $before,
            'after' => ['role' => $user->role, 'account_status' => $user->account_status],
        ]));

        return back()->with('success', 'User updated.');
    }

    // -------------------------------------------------------------- content & FAQs

    public function faqs()
    {
        return view('admin.faqs', ['faqs' => Faq::orderBy('category')->orderBy('sort_order')->paginate(20)]);
    }

    public function storeFaq(StoreFaqRequest $request)
    {
        $faq = Faq::create($request->validated() + ['is_published' => $request->boolean('is_published', true)]);
        AuditLog::record('faq_created', Faq::class, $faq->id, $faq->question);

        return back()->with('success', 'FAQ saved.');
    }

    public function destroyFaq(Request $request, Faq $faq)
    {
        AuditLog::record('faq_deleted', Faq::class, $faq->id, $faq->question);
        $faq->delete();

        return back()->with('success', 'FAQ deleted.');
    }

    public function places()
    {
        return view('admin.places', [
            'places' => LocalService::with('centre:id,name,city')->latest('id')->paginate(20),
            'centres' => ExamCentre::orderByRaw('LOWER(city) asc')->limit(200)->get(['id', 'name', 'city']),
            'categories' => config('exam_travel.service_categories'),
            'statuses' => DataStatus::values(),
        ]);
    }

    public function storePlace(StorePlaceRequest $request)
    {
        $data = $request->validated();
        $data['source'] = 'admin_curated';
        $data['source_type'] = 'curated';
        $data['source_name'] = $data['source_name'] ?? 'Administrator entry';
        $data['last_verified_at'] = in_array($data['data_status'], [
            DataStatus::OfficiallyVerified->value, DataStatus::AdminVerified->value,
        ], true) ? now() : null;
        $data['is_demo'] = false;

        $place = LocalService::create($data);
        AuditLog::record('place_created', LocalService::class, $place->id, $place->name.' ('.$place->source_name.')');

        return back()->with('success', 'Listing added with its source recorded.');
    }

    public function destroyPlace(Request $request, LocalService $place)
    {
        AuditLog::record('place_deleted', LocalService::class, $place->id, $place->name);
        $place->delete();

        return back()->with('success', 'Listing removed.');
    }

    // ------------------------------------------------------------------ moderation

    public function reports()
    {
        return view('admin.reports', [
            'reports' => ContentReport::with(['reporter:id,name'])
                ->where('status', 'pending')
                ->latest('id')
                ->paginate(20),
            'resolved' => ContentReport::with('moderator:id,name')
                ->where('status', '!=', 'pending')
                ->latest('id')
                ->limit(10)
                ->get(),
            'reportedAnswers' => ChatMessage::reported()
                ->with('conversation.user:id,name')
                ->latest('reported_at')
                ->paginate(10, ['*'], 'answers'),
        ]);
    }

    public function resolveReport(ResolveReportRequest $request, ContentReport $report)
    {
        $data = $request->validated();

        if (in_array($data['action'], ['hide', 'remove'], true) && class_exists($report->reportable_type)) {
            $item = $report->reportable_type::find($report->reportable_id);

            if ($item) {
                $item->forceFill(['status' => $data['action'] === 'hide' ? 'hidden' : 'removed'])->save();
            }
        }

        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
            'moderator_note' => $data['moderator_note'] ?? null,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        AuditLog::record(
            'report_'.$data['action'],
            ContentReport::class,
            $report->id,
            $report->reportable_type.'#'.$report->reportable_id,
        );

        return back()->with('success', 'Report resolved and the decision recorded in the audit trail.');
    }

    public function audits(Request $request)
    {
        return view('admin.audits', [
            'logs' => AuditLog::with('user:id,name')
                ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->input('action').'%'))
                ->latest('id')
                ->paginate(40)
                ->withQueryString(),
        ]);
    }

    public function staleCentres()
    {
        $window = (int) config('exam_travel.freshness.centre_months', 6);

        return view('admin.stale', [
            'window' => $window,
            'centres' => ExamCentre::query()->active()
                ->where(fn ($q) => $q->whereNull('last_verified_at')
                    ->orWhere('last_verified_at', '<', now()->subMonths($window)))
                ->orderBy('last_verified_at')
                ->paginate(30),
        ]);
    }
}
