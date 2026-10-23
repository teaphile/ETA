<?php

namespace App\Services;

use App\Contracts\AIProvider;
use App\Integrations\Support\ChatAnswer;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Itinerary;
use App\Models\User;

/**
 * Chatbot orchestration.
 *
 * Responsibilities, in order:
 *  1. Build grounded context from the platform's own data (read-only tools only).
 *  2. Ask the configured AIProvider for an answer using *that* context.
 *  3. Persist a controlled-retention transcript for signed-in users.
 *
 * The model never decides what is true; it may only phrase what the retrieval layer
 * found. If nothing was found, the answer says so.
 */
class ChatbotService
{
    public function __construct(
        private readonly AssistantContextBuilder $contextBuilder,
        private readonly AIProvider $ai,
    ) {}

    public function ask(string $question, ?User $user = null, ?Itinerary $itinerary = null): ChatAnswer
    {
        $question = mb_substr(trim($question), 0, (int) config('exam_travel.assistant.max_question_length', 1000));

        $context = $this->contextBuilder->retrieve($question, $itinerary);

        $answer = $this->ai->answer($question, $context);

        if ($user && ! $user->isSuspended()) {
            $this->persist($user, $question, $answer);
        }

        return $answer;
    }

    public function providerName(): string
    {
        return $this->ai->name();
    }

    public function providerAvailable(): bool
    {
        return $this->ai->isAvailable();
    }

    /** Grounded context for a conversation, exposed for tests and debugging. */
    public function contextFor(string $question, ?Itinerary $itinerary = null): array
    {
        return $this->contextBuilder->retrieve($question, $itinerary);
    }

    public function conversationFor(User $user): ChatConversation
    {
        return ChatConversation::firstOrCreate(['user_id' => $user->id]);
    }

    private function persist(User $user, string $question, ChatAnswer $answer): void
    {
        try {
            $conversation = $this->conversationFor($user);

            if ($conversation->messages()->count() >= (int) config('exam_travel.assistant.max_messages_per_conversation', 200)) {
                $conversation->messages()->oldest()->limit(20)->delete();
            }

            ChatMessage::create([
                'chat_conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $question,
            ]);

            ChatMessage::create([
                'chat_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'provider' => $answer->provider,
                'category' => $answer->category,
                'content' => $answer->answer,
                'sources' => $answer->sources,
                'limitation' => $answer->limitation,
            ]);

            $conversation->touch();
        } catch (\Throwable $e) {
            // A transcript failure must never break the assistant reply.
            report($e);
        }
    }

    /** Retention: delete transcripts older than the documented window. */
    public function prune(int $days): int
    {
        return ChatConversation::where('updated_at', '<', now()->subDays($days))->delete();
    }
}
