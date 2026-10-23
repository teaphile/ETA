<?php

namespace App\Http\Controllers;

use App\Contracts\NotificationProvider;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, NotificationProvider $notifications)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'current_city' => $data['current_city'] ?? null,
            'state' => $data['state'] ?? null,
            'preferred_language' => $data['preferred_language'] ?? config('app.locale'),
            'password' => $data['password'],
            'role' => User::ROLE_STUDENT,
            'account_status' => 'active',
            'terms_accepted_at' => now(),
        ]);

        // Email verification is available but not enforced by default; see
        // config/exam_travel.php and docs/SECURITY_AND_PRIVACY.md.
        if (config('exam_travel.security.require_email_verification', false)) {
            $user->sendEmailVerificationNotification();
        }

        $notifications->send($user, 'welcome', [
            'title' => 'Welcome to Exam Travel Assist',
            'body' => "Namaste {$user->name},\n\nYour free account is ready. You can now save itineraries, save useful places near your centre, and ask the travel assistant.",
            'action_url' => url('/centres'),
            'action_label' => 'Find your exam centre',
        ]);

        AuditLog::record('user_registered', 'User', $user->id, 'self-registration');

        Auth::login($user);
        $request->session()->regenerate();

        // When strict verification is enabled the student is sent to the notice page;
        // otherwise (the default) they can start using the platform immediately.
        if (config('exam_travel.security.require_email_verification', false) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('success', __('messages.auth.registered'));
        }

        return redirect()->route('dashboard')->with('success', __('messages.auth.registered'));
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('messages.auth.invalid_credentials')])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => __('messages.auth.suspended')])->onlyInput('email');
        }

        if ($user->account_status === 'deleted') {
            Auth::logout();

            return back()->withErrors(['email' => __('messages.auth.invalid_credentials')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('messages.auth.logged_out'));
    }

    // ------------------------------------------------------------- email verification

    public function showVerificationNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = $request->user();

        // The link is signed *and* bound to the signed-in account, so a forwarded link
        // cannot be used to verify a different student's address.
        abort_unless(
            $user instanceof User
                && (string) $user->getKey() === (string) $id
                && hash_equals((string) $hash, sha1($user->getEmailForVerification())),
            403
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            AuditLog::record('email_verified', User::class, $user->id, 'verified via emailed link');
        }

        return redirect()->route('dashboard')->with('success', __('messages.auth.email_verified'));
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('success', __('messages.auth.already_verified'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', __('messages.auth.verification_sent'));
    }

    // ------------------------------------------------------------- password reset

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->validated());

        // The response is identical whether or not the address exists, so the endpoint
        // cannot be used to enumerate registered students.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __('messages.auth.reset_throttled')]);
        }

        return back()->with('success', __('messages.auth.reset_link_sent'));
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::record('password_reset', 'User', $user->id, 'completed via emailed token');
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', __('messages.auth.password_reset_done'));
    }

    // ----------------------------------------------------------------- profile

    public function profile(Request $request)
    {
        return view('dashboard.profile', [
            'user' => $request->user(),
            'deletionRequested' => $request->user()->hasPendingDeletion(),
            'canSelfDelete' => app(AccountDeletionService::class)->canSelfDelete($request->user()),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->with('success', __('messages.profile.updated'));
    }

    public function requestDeletion(Request $request, NotificationProvider $notifications)
    {
        $request->user()->forceFill(['deletion_requested_at' => now()])->save();

        AuditLog::record('account_deletion_requested', 'User', $request->user()->id, 'student-initiated');

        $notifications->send($request->user(), 'deletion_requested', [
            'title' => 'We received your deletion request',
            'body' => 'We recorded your request on '.now()->format('d M Y').'. Your itineraries, saved places and chat history will be removed and your profile anonymised within '.config('exam_travel.retention.deletion_grace_days', 30).' days. Reply to this email if you change your mind before then.',
        ]);

        return back()->with('success', __('messages.profile.deletion_requested', [
            'days' => config('exam_travel.retention.deletion_grace_days', 30),
        ]));
    }

    public function cancelDeletion(Request $request)
    {
        $request->user()->forceFill(['deletion_requested_at' => null])->save();
        AuditLog::record('account_deletion_cancelled', 'User', $request->user()->id);

        return back()->with('success', __('messages.profile.deletion_cancelled'));
    }

    /** Immediate, self-service deletion. Requires the current password as confirmation. */
    public function destroy(
        Request $request,
        AccountDeletionService $deletion,
        NotificationProvider $notifications,
    ) {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'password.current_password' => __('messages.profile.wrong_password'),
            'confirm.in' => __('messages.profile.confirm_delete_hint'),
        ]);

        $user = $request->user();

        if (! $deletion->canSelfDelete($user)) {
            return back()->withErrors(['confirm' => __('messages.profile.admin_cannot_delete')]);
        }

        $email = $user->email;
        $removed = $deletion->anonymise($user);

        AuditLog::record('account_deleted', 'User', $user->id, 'self-service; removed '.json_encode($removed['removed']));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Notification goes to the address captured before anonymisation.
        $notifications->send(
            new User(['email' => $email, 'name' => 'Deleted user']),
            'account_deleted',
            ['title' => 'Your account has been deleted', 'body' => 'Your Exam Travel Assist account and personal data have been removed.'],
        );

        return redirect('/')->with('success', __('messages.profile.deleted'));
    }
}
