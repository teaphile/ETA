<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportContentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\StoreReplyRequest;
use App\Models\AuditLog;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\ContentReport;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $posts = CommunityPost::query()
            ->visible()
            ->with(['user:id,name', 'examination:id,name'])
            ->withCount(['replies' => fn ($q) => $q->where('status', 'visible')])
            ->when($request->filled('city'), function ($q) use ($request) {
                $q->whereRaw('LOWER(city) LIKE ?', ['%'.mb_strtolower(trim((string) $request->input('city'))).'%']);
            })
            ->when($request->filled('exam'), fn ($q) => $q->where('examination_id', $request->integer('exam')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.mb_strtolower(trim((string) $request->input('q'))).'%';
                $q->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(title) LIKE ?', [$like])->orWhereRaw('LOWER(body) LIKE ?', [$like]);
                });
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('community.index', [
            'posts' => $posts,
            'examinations' => Examination::query()->active()->orderBy('name')->get(),
            'categories' => config('exam_travel.community_categories'),
        ]);
    }

    public function create()
    {
        return view('community.create', [
            'examinations' => Examination::query()->active()->orderBy('name')->get(),
            'categories' => config('exam_travel.community_categories'),
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $post = CommunityPost::create($request->validated() + [
            'user_id' => $request->user()->id,
            'status' => 'visible',
        ]);

        return redirect()
            ->route('community.show', $post)
            ->with('success', __('messages.community.posted'));
    }

    public function show(Request $request, CommunityPost $post)
    {
        $viewer = $request->user();
        $isModerator = $viewer?->canModerateContent() ?? false;

        abort_if(! $post->isVisible() && ! $isModerator, 404);

        $post->load(['user:id,name,role', 'examination:id,name']);

        $replies = $post->replies()
            ->when(! $isModerator, fn ($q) => $q->where('status', 'visible'))
            ->with('user:id,name,role')
            ->oldest('id')
            ->paginate(20);

        return view('community.show', [
            'post' => $post,
            'replies' => $replies,
            'isModerator' => $isModerator,
        ]);
    }

    public function reply(StoreReplyRequest $request, CommunityPost $post)
    {
        // Hidden or locked threads cannot receive new replies.
        abort_if(! $post->canReceiveReplies(), 403, __('messages.community.locked'));

        $reply = CommunityReply::create([
            'community_post_id' => $post->id,
            'user_id' => $request->user()->id,
            'body' => $request->string('body')->toString(),
            'status' => 'visible',
        ]);

        $post->forceFill(['last_reply_at' => now()])->save();

        return back()->with('success', 'Reply posted.');
    }

    public function report(ReportContentRequest $request)
    {
        $class = $request->targetClass();

        abort_unless($class::whereKey($request->integer('target_id'))->exists(), 404);

        $already = ContentReport::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reportable_type', $class)
            ->where('reportable_id', $request->integer('target_id'))
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return back()->with('success', 'You have already reported this content. A moderator will review it.');
        }

        ContentReport::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $class,
            'reportable_id' => $request->integer('target_id'),
            'reason' => $request->string('reason')->toString(),
            'status' => 'pending',
        ]);

        DB::table('community_posts')
            ->whereKey($class === CommunityPost::class
                ? $request->integer('target_id')
                : (int) CommunityReply::whereKey($request->integer('target_id'))->value('community_post_id'))
            ->increment('reports_count');

        return back()->with('success', __('messages.community.reported'));
    }

    /** Moderator actions: hide, restore or lock a thread. */
    public function moderate(Request $request, CommunityPost $post)
    {
        $data = $request->validate([
            'action' => ['required', 'in:hide,restore,lock,unlock,remove'],
        ]);

        $post->forceFill(match ($data['action']) {
            'hide' => ['status' => 'hidden'],
            'remove' => ['status' => 'removed'],
            'restore' => ['status' => 'visible'],
            'lock' => ['is_locked' => true],
            'unlock' => ['is_locked' => false],
        })->save();

        AuditLog::record(
            'community_post_'.$data['action'],
            CommunityPost::class,
            $post->id,
            'moderator action',
        );

        return back()->with('success', 'Moderation action applied.');
    }
}
