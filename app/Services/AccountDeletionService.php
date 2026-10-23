<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\CommunityReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Irreversible privacy action: anonymise an account instead of hard-deleting the row.
 *
 * Why anonymise: community threads other students replied to must not vanish, but every
 * personal identifier must. The row keeps an unpredictable placeholder email so the
 * unique constraint still protects the original address from being reused.
 *
 * Nothing here is reversible, so callers must confirm the current password first.
 */
class AccountDeletionService
{
    /**
     * @return array{anonymised:bool,removed:array<string,int>}
     */
    public function anonymise(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $removed = [
                'itineraries' => $user->itineraries()->count(),
                'chat_conversations' => ChatConversation::where('user_id', $user->id)->count(),
                'saved_places' => DB::table('saved_places')->where('user_id', $user->id)->count(),
                'notifications' => DB::table('notifications')
                    ->where('notifiable_type', $user->getMorphClass())
                    ->where('notifiable_id', $user->id)
                    ->count(),
            ];

            // Personal content is removed outright.
            $user->itineraries()->delete();
            ChatConversation::where('user_id', $user->id)->delete();
            DB::table('saved_places')->where('user_id', $user->id)->delete();
            DB::table('notifications')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->id)
                ->delete();

            // Community content is retained but detached from the person, so the
            // author's identity and exact details are no longer exposed.
            CommunityReply::where('user_id', $user->id)->update(['body' => '[removed with account deletion]']);

            $user->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted+'.$user->id.'-'.Str::lower(Str::random(12)).'@invalid.local',
                'password' => bcrypt(Str::random(64)),
                'phone' => null,
                'current_city' => null,
                'state' => null,
                'remember_token' => null,
                'account_status' => 'deleted',
                'deletion_requested_at' => now(),
                'email_verified_at' => null,
            ])->save();

            return ['anonymised' => true, 'removed' => $removed];
        });
    }

    public function canSelfDelete(User $user): bool
    {
        return ! $user->isAdmin();
    }
}
