<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskAssistantRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Itinerary;
use App\Services\ChatbotService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request, ChatbotService $bot)
    {
        $history = [];

        if ($request->user()) {
            $conversation = ChatConversation::where('user_id', $request->user()->id)->latest('id')->first();
            $history = $conversation
                ? $conversation->messages()->latest('id')->limit(20)->get()->reverse()->values()
                : collect();
        }

        return view('chat.index', [
            'history' => $history,
            'provider' => $bot->providerName(),
            'providerAvailable' => $bot->providerAvailable(),
            'retentionDays' => (int) config('exam_travel.retention.chat_days', 90),
        ]);
    }

    public function ask(AskAssistantRequest $request, ChatbotService $bot)
    {
        $itinerary = null;

        if ($request->filled('itinerary_id') && $request->user()) {
            $candidate = Itinerary::find($request->integer('itinerary_id'));
            // Ownership check: a student can never ground an answer in someone else's plan.
            $itinerary = ($candidate && $candidate->isOwnedBy($request->user())) ? $candidate : null;
        }

        $answer = $bot->ask($request->string('message')->toString(), $request->user(), $itinerary);

        $payload = $answer->toArray();
        $payload['message_id'] = $this->latestAssistantMessageId($request);

        return response()->json($payload);
    }

    public function report(Request $request)
    {
        $data = $request->validate([
            'message_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $message = ChatMessage::with('conversation')->findOrFail($data['message_id']);

        // Only the owner of the transcript may report its content.
        abort_unless($message->conversation?->user_id === $request->user()->id, 403);
        abort_unless($message->role === 'assistant', 422);

        $message->forceFill([
            'reported_at' => now(),
            'report_reason' => $data['reason'] ?? null,
        ])->save();

        return back()->with('success', __('messages.assistant.reported'));
    }

    public function clear(Request $request)
    {
        ChatConversation::where('user_id', $request->user()->id)->delete();

        return back()->with('success', __('messages.assistant.cleared'));
    }

    private function latestAssistantMessageId(Request $request): ?int
    {
        if (! $request->user()) {
            return null;
        }

        return ChatMessage::query()
            ->whereHas('conversation', fn ($q) => $q->where('user_id', $request->user()->id))
            ->where('role', 'assistant')
            ->latest('id')
            ->value('id');
    }
}
