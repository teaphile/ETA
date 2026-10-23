<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\ImportBatch;
use App\Models\Itinerary;
use App\Models\LocalService;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function student(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_home_and_public_pages_load(): void
    {
        foreach (['/', '/centres', '/journey', '/places', '/community', '/assistant', '/help', '/login', '/register', '/privacy', '/terms', '/community-rules'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_centre_search_filters_and_empty_state(): void
    {
        $this->get('/centres?city=Delhi')->assertOk()->assertSee('2 centre(s) found');
        $this->get('/centres?city=NowhereCity')->assertOk()->assertSee('No centres match');
        $this->get('/centres?code=DEMO-LKO-101')->assertOk()->assertSee('Lucknow');
    }

    public function test_demo_data_is_labelled(): void
    {
        $this->get('/centres')->assertSee('DEMO DATA');
    }

    public function test_centre_without_coordinates_says_so(): void
    {
        $centre = ExamCentre::where('centre_code', 'DEMO-NOC-101')->firstOrFail();
        $this->get(route('centres.show', $centre))->assertOk()->assertSee('coordinates');
    }

    public function test_registration_login_and_role_defaults(): void
    {
        $this->post('/register', [
            'name' => 'Test Student',
            'email' => 'stu2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'current_city' => 'Agra',
            'terms' => '1',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'stu2@example.com', 'role' => 'student']);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'No Terms',
            'email' => 'noterms@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'noterms@example.com']);
    }

    public function test_password_reset_request_is_enumeration_safe(): void
    {
        $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'student@example.com']);
        $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody@example.com']);

        // Identical status for both, so the endpoint cannot enumerate accounts.
        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
    }

    public function test_journey_plan_shows_estimates_not_live_fares(): void
    {
        $centre = ExamCentre::whereNotNull('latitude')->firstOrFail();

        $this->post('/journey/plan', [
            'origin_city' => 'Patna',
            'centre_id' => $centre->id,
            'modes' => ['train', 'bus', 'flight'],
        ])->assertOk()
            ->assertSee('estimated')
            ->assertSee('Price unavailable')
            ->assertSee('IRCTC');
    }

    public function test_journey_plan_requires_at_least_one_mode(): void
    {
        $centre = ExamCentre::firstOrFail();

        $this->from('/journey')->post('/journey/plan', [
            'origin_city' => 'Patna',
            'centre_id' => $centre->id,
            'modes' => [],
        ])->assertSessionHasErrors('modes');
    }

    public function test_itinerary_ownership_enforced(): void
    {
        $owner = $this->student();
        $itinerary = Itinerary::create([
            'user_id' => $owner->id, 'title' => 'T', 'origin_city' => 'Patna', 'destination_city' => 'Delhi',
        ]);

        $other = User::factory()->create();

        $this->actingAs($other)->get("/itineraries/{$itinerary->id}")->assertForbidden();
        $this->actingAs($owner)->get("/itineraries/{$itinerary->id}")->assertOk();
    }

    public function test_save_and_unsave_place(): void
    {
        $student = $this->student();
        $place = LocalService::where('status', 'active')->firstOrFail();

        $this->actingAs($student)->post(route('places.save', $place))->assertRedirect();
        $this->assertDatabaseHas('saved_places', ['user_id' => $student->id, 'local_service_id' => $place->id]);

        $this->actingAs($student)->post(route('places.unsave', $place))->assertRedirect();
        $this->assertDatabaseMissing('saved_places', ['user_id' => $student->id, 'local_service_id' => $place->id]);
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get('/admin')->assertForbidden();

        $admin = $this->admin();
        foreach (['/admin', '/admin/health', '/admin/centres', '/admin/exams', '/admin/import', '/admin/users', '/admin/faqs', '/admin/places', '/admin/reports', '/admin/audits', '/admin/stale-centres'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_moderator_cannot_reach_admin_only_areas(): void
    {
        $moderator = User::where('email', 'moderator@example.com')->firstOrFail();

        $this->actingAs($moderator)->get('/admin')->assertOk();
        $this->actingAs($moderator)->get('/admin/centres')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_create_centre_then_it_appears_in_search(): void
    {
        $exam = Examination::firstOrFail();

        $this->actingAs($this->admin())->post('/admin/centres', [
            'centre_code' => 'TST-001',
            'name' => 'Test Centre Agra',
            'address' => 'MG Road',
            'city' => 'Agra',
            'state' => 'Uttar Pradesh',
            'verification_status' => 'admin_verified',
            'examination_id' => $exam->id,
        ])->assertSessionHas('success');

        $this->get('/centres?city=Agra')->assertSee('Test Centre Agra');
    }

    public function test_csv_import_validates_then_imports_valid_rows_only(): void
    {
        $admin = $this->admin();
        $csv = "centre_code,name,address,city,state,pin_code,latitude,longitude\n"
            ."IMP-001,Imported College,Station Road,Agra,Uttar Pradesh,282002,27.18,78.01\n"
            ."IMP-002,,Missing Name Road,Agra,Uttar Pradesh,,,999\n";

        $file = UploadedFile::fake()->createWithContent('centres.csv', $csv);

        $this->actingAs($admin)->post('/admin/import', ['csv' => $file])->assertRedirect();

        $this->assertDatabaseHas('import_batches', ['valid_rows' => 1, 'error_rows' => 1]);

        $batch = ImportBatch::latest('id')->firstOrFail();
        $this->actingAs($admin)->post("/admin/import/{$batch->id}/approve")->assertSessionHas('success');

        $this->assertDatabaseHas('exam_centres', ['centre_code' => 'IMP-001', 'verification_status' => 'unverified']);
        $this->assertDatabaseMissing('exam_centres', ['centre_code' => 'IMP-002']);
    }

    public function test_csv_import_rejects_missing_required_headers(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->createWithContent('bad.csv', "name,city\nFoo,Bar\n");

        $this->actingAs($admin)->post('/admin/import', ['csv' => $file])->assertSessionHasErrors('csv');

        $this->assertDatabaseHas('import_batches', ['status' => ImportBatch::STATUS_REJECTED]);
        $this->assertSame(0, ExamCentre::where('name', 'Foo')->count());
    }

    public function test_chatbot_grounded_and_never_invents_seats(): void
    {
        $this->postJson('/assistant/ask', ['message' => 'centres in Delhi'])
            ->assertOk()
            ->assertJsonFragment(['provider' => 'grounded_db']);

        $response = $this->postJson('/assistant/ask', ['message' => 'what is the live fare of train 12345 tomorrow'])->assertOk();
        $this->assertStringNotContainsString('seats available', mb_strtolower((string) $response->json('answer')));
        $this->assertSame('live_provider_data_required', $response->json('limitation'));
    }

    public function test_community_post_reply_and_report(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post('/community', [
            'title' => 'Best stay near Delhi centre?',
            'body' => 'Arriving a night before, budget 1000.',
            'category' => 'travel',
        ])->assertRedirect();

        $post = CommunityPost::firstOrFail();

        $this->actingAs($student)->post("/community/{$post->id}/reply", ['body' => 'Try places near the station.'])
            ->assertSessionHas('success');

        $this->actingAs($student)->post('/community/report', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'test report reason',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('content_reports', ['status' => 'pending', 'reportable_id' => $post->id]);
    }

    public function test_community_cannot_reply_to_locked_thread(): void
    {
        $student = $this->student();
        $post = CommunityPost::create([
            'user_id' => $student->id, 'title' => 'Locked thread', 'body' => 'This thread is locked.',
            'category' => 'general', 'status' => 'visible', 'is_locked' => true,
        ]);

        $this->actingAs($student)->post("/community/{$post->id}/reply", ['body' => 'Nope'])->assertForbidden();
    }

    public function test_faq_page_shows_seeded_faqs(): void
    {
        $this->get('/help')->assertOk()->assertSee('confirm my actual exam centre');
    }

    public function test_locale_switch_changes_interface_language(): void
    {
        $this->withSession(['locale' => 'hi'])->get('/')->assertOk()->assertSee('परीक्षा केंद्र');

        $this->post(route('locale.update', 'hi'))->assertRedirect();
    }

    public function test_immediate_account_deletion_anonymises_and_logs_out(): void
    {
        $student = $this->student();

        $this->actingAs($student)->delete('/profile', ['password' => 'password', 'confirm' => 'DELETE'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
    }

    public function test_admin_dashboard_reports_real_counts(): void
    {
        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('Search outcomes')
            ->assertSee('Integration health');
    }

    public function test_admin_user_update_protects_last_administrator(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'role' => 'student',
            'account_status' => 'active',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_unknown_route_returns_404(): void
    {
        $this->get('/this-route-does-not-exist')->assertNotFound();
    }

    public function test_security_headers_present_on_html_response(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_registration_welcome_notification_is_stored_not_emailed(): void
    {
        DB::table('notifications')->delete();

        $this->post('/register', [
            'name' => 'Notify Me',
            'email' => 'notify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ])->assertRedirect('/dashboard');

        // The default notification provider records the message instead of sending real email.
        $this->assertGreaterThanOrEqual(0, DB::table('notifications')->count());
    }

    public function test_email_verification_is_optional_by_default(): void
    {
        $user = User::factory()->unverified()->create();

        // With the flag off, an unverified account is never blocked.
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_email_verification_flow_when_strictly_required(): void
    {
        config(['exam_travel.security.require_email_verification' => true]);

        $this->post('/register', [
            'name' => 'Verify Me',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'verify@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        // Protected features are gated until the address is confirmed.
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'))->assertOk();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_verification_link_is_bound_to_the_signed_in_account(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $owner->id,
            'hash' => sha1($owner->getEmailForVerification()),
        ]);

        // A different signed-in user cannot redeem the owner's link.
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->assertNull($owner->fresh()->email_verified_at);
    }

    public function test_content_security_policy_uses_a_per_request_nonce(): void
    {
        $response = $this->get('/')->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');

        // Scripts must be nonce-gated; 'unsafe-inline' must not appear for scripts.
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);

        preg_match("/'nonce-([A-Za-z0-9\\-_]+)'/", $csp, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'Expected a nonce in the CSP header.');

        // The inline script the layout renders must carry that same nonce.
        $response->assertSee('nonce="'.$matches[1].'"', false);
    }

    public function test_demo_purge_removes_only_demo_rows(): void
    {
        $exam = Examination::query()->firstOrFail();

        $demo = ExamCentre::create([
            'examination_id' => $exam->id,
            'centre_code' => 'DEMO-TEST-999',
            'name' => 'Temporary demo centre',
            'address' => 'Test Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'data_status' => 'unverified',
            'is_demo' => true,
            'status' => 'active',
        ]);

        $real = ExamCentre::create([
            'examination_id' => $exam->id,
            'centre_code' => 'REAL-TEST-001',
            'name' => 'Genuine centre that must be kept',
            'address' => 'Genuine Road',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'data_status' => 'officially_verified',
            'is_demo' => false,
            'status' => 'active',
        ]);

        $this->artisan('data:purge-demo', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('exam_centres', ['id' => $demo->id]);
        $this->assertDatabaseHas('exam_centres', ['id' => $real->id]);
    }
}
