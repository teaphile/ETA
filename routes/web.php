<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CentreController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Exam Travel Assist — web routes
|--------------------------------------------------------------------------
| Guests can search centres and plan journeys without an account. Everything that
| creates or exposes personal data is behind `auth`, and every administrative
| action is behind `auth` + `role`. Rate limits are named limiters defined in
| AppServiceProvider so paid/abusable endpoints are protected centrally.
*/

// ------------------------------------------------------------------ public
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/centres', [CentreController::class, 'index'])->name('centres.index');
Route::get('/centres/{centre}', [CentreController::class, 'show'])->name('centres.show');

Route::get('/journey', [JourneyController::class, 'index'])->name('journey.index');
Route::post('/journey/plan', [JourneyController::class, 'plan'])
    ->middleware('throttle:journey')
    ->name('journey.plan');

Route::get('/places', [PlaceController::class, 'index'])
    ->middleware('throttle:places')
    ->name('places.index');

Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
// Must be declared before the {post} route so "create" is not captured as an id.
Route::middleware('auth')->get('/community/create', [CommunityController::class, 'create'])->name('community.create');
Route::get('/community/{post}', [CommunityController::class, 'show'])->name('community.show');
Route::get('/assistant', [ChatController::class, 'index'])->name('assistant.index');
Route::post('/assistant/ask', [ChatController::class, 'ask'])
    ->middleware('throttle:assistant')
    ->name('assistant.ask');

Route::get('/help', [HelpController::class, 'index'])->name('help.index');
Route::get('/privacy', [HelpController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [HelpController::class, 'terms'])->name('legal.terms');
Route::get('/community-rules', [HelpController::class, 'communityRules'])->name('legal.community-rules');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// ------------------------------------------------------------------ guests only
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

// ------------------------------------------------------------------ students
Route::middleware('auth')->group(function () {
    // Available to any signed-in user, verified or not: an unverified student must
    // still be able to reach the notice, resend the link, edit their profile and log out.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/deletion', [AuthController::class, 'requestDeletion'])->name('profile.deletion.request');
    Route::delete('/profile/deletion', [AuthController::class, 'cancelDeletion'])->name('profile.deletion.cancel');
    Route::delete('/profile', [AuthController::class, 'destroy'])->name('profile.destroy');

    // Student features. The `verified` middleware is a no-op unless the operator sets
    // REQUIRE_EMAIL_VERIFICATION=true (see App\Http\Middleware\EnsureEmailVerified).
    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/itineraries', [ItineraryController::class, 'index'])->name('itineraries.index');
        Route::get('/itineraries/{itinerary}', [ItineraryController::class, 'show'])->name('itineraries.show');
        Route::delete('/itineraries/{itinerary}', [ItineraryController::class, 'destroy'])->name('itineraries.destroy');

        Route::post('/places/{place}/save', [PlaceController::class, 'save'])->name('places.save');
        Route::post('/places/{place}/unsave', [PlaceController::class, 'unsave'])->name('places.unsave');
        Route::get('/saved-places', [PlaceController::class, 'saved'])->name('places.saved');

        Route::post('/community', [CommunityController::class, 'store'])
            ->middleware('throttle:community-write')
            ->name('community.store');
        Route::post('/community/{post}/reply', [CommunityController::class, 'reply'])
            ->middleware('throttle:community-write')
            ->name('community.reply');
        Route::post('/community/report', [CommunityController::class, 'report'])
            ->middleware('throttle:reports')
            ->name('community.report');

        Route::post('/assistant/report', [ChatController::class, 'report'])
            ->middleware('throttle:reports')
            ->name('assistant.report');
        Route::delete('/assistant/history', [ChatController::class, 'clear'])->name('assistant.clear');

        // Moderators and administrators only.
        Route::post('/community/{post}/moderate', [CommunityController::class, 'moderate'])
            ->middleware('role:admin,moderator')
            ->name('community.moderate');
    });
});

// ------------------------------------------------------------------ staff
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,moderator'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/health', [AdminController::class, 'health'])->name('health');

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::post('/reports/{report}/resolve', [AdminController::class, 'resolveReport'])->name('reports.resolve');

    Route::get('/stale-centres', [AdminController::class, 'staleCentres'])->name('stale');

    // Administrator-only area (least privilege: moderators are deliberately excluded).
    Route::middleware('role:admin')->group(function () {
        Route::get('/exams', [AdminController::class, 'exams'])->name('exams');
        Route::post('/exams', [AdminController::class, 'storeExam'])->name('exams.store');
        Route::post('/sessions', [AdminController::class, 'storeSession'])->name('sessions.store');

        Route::get('/centres', [AdminController::class, 'centres'])->name('centres');
        Route::post('/centres', [AdminController::class, 'storeCentre'])->name('centres.store');
        Route::put('/centres/{centre}', [AdminController::class, 'updateCentre'])->name('centres.update');
        Route::post('/centres/{centre}/verify', [AdminController::class, 'verifyCentre'])->name('centres.verify');
        Route::delete('/centres/{centre}', [AdminController::class, 'destroyCentre'])->name('centres.archive');

        Route::get('/import', [AdminController::class, 'importForm'])->name('import');
        Route::get('/import/template', [AdminController::class, 'importTemplate'])->name('import.template');
        Route::post('/import', [AdminController::class, 'importUpload'])
            ->middleware('throttle:admin-import')
            ->name('import.upload');
        Route::get('/import/{batch}', [AdminController::class, 'importShow'])->name('import.show');
        Route::post('/import/{batch}/approve', [AdminController::class, 'importApprove'])->name('import.approve');
        Route::post('/import/{batch}/reject', [AdminController::class, 'importReject'])->name('import.reject');

        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');

        Route::get('/faqs', [AdminController::class, 'faqs'])->name('faqs');
        Route::post('/faqs', [AdminController::class, 'storeFaq'])->name('faqs.store');
        Route::delete('/faqs/{faq}', [AdminController::class, 'destroyFaq'])->name('faqs.destroy');

        Route::get('/places', [AdminController::class, 'places'])->name('places');
        Route::post('/places', [AdminController::class, 'storePlace'])->name('places.store');
        Route::delete('/places/{place}', [AdminController::class, 'destroyPlace'])->name('places.destroy');

        Route::get('/audits', [AdminController::class, 'audits'])->name('audits');
    });
});
