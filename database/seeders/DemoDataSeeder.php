<?php

namespace Database\Seeders;

use App\Integrations\Support\DataStatus;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\ExamSession;
use App\Models\LocalService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demonstration data — DEVELOPER/TESTING USE ONLY.
 *
 * Every centre and listing created here is flagged `is_demo = true` and rendered with a
 * prominent "DEMO DATA" badge, so demonstration rows can never be mistaken for real
 * official information. The names are deliberately obviously fake.
 *
 * This seeder refuses to run in production unless ALLOW_DEMO_SEED=true is set
 * explicitly, so a production deployment cannot silently fill the centre table with
 * invented records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && env('ALLOW_DEMO_SEED') !== 'true') {
            $this->command?->warn('Refusing to seed demo data in production. Set ALLOW_DEMO_SEED=true if you really intend to.');

            return;
        }

        // Demo accounts use an obviously weak shared password and are created only in
        // non-production environments. Use `php artisan app:create-admin` in production.
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Demo Administrator', 'password' => Hash::make('password'), 'role' => User::ROLE_ADMIN,
                'current_city' => 'Delhi', 'state' => 'Delhi', 'account_status' => 'active', 'email_verified_at' => now()],
        );

        User::updateOrCreate(
            ['email' => 'moderator@example.com'],
            ['name' => 'Demo Moderator', 'password' => Hash::make('password'), 'role' => User::ROLE_MODERATOR, 'account_status' => 'active', 'email_verified_at' => now()],
        );

        User::updateOrCreate(
            ['email' => 'student@example.com'],
            ['name' => 'Anjali Verma', 'password' => Hash::make('password'), 'role' => User::ROLE_STUDENT,
                'current_city' => 'Patna', 'state' => 'Bihar', 'account_status' => 'active', 'email_verified_at' => now()],
        );

        $jee = Examination::where('slug', 'jee-main')->first();
        $neet = Examination::where('slug', 'neet-ug')->first();
        $ssc = Examination::where('slug', 'ssc-cgl')->first();

        $session = $jee ? ExamSession::updateOrCreate(
            ['examination_id' => $jee->id, 'name' => 'Demo Session 2026'],
            ['year' => 2026, 'status' => 'upcoming'],
        ) : null;

        $centres = [
            [$jee, 'DEMO-DEL-101', 'Demo Institute of Technology (Demo)', 'Education Hub Road, Dwarka', 'Delhi', 'Delhi', '110078', 28.5921, 77.0460],
            [$neet, 'DEMO-DEL-201', 'Demo Public School, Rohini (Demo)', 'Sector 7, Rohini', 'Delhi', 'Delhi', '110085', 28.7495, 77.0565],
            [$ssc, 'DEMO-LKO-101', 'Demo Degree College (Demo)', 'University Road', 'Lucknow', 'Uttar Pradesh', '226007', 26.8654, 80.9150],
            [$jee, 'DEMO-PAT-101', 'Demo Science College (Demo)', 'Ashok Rajpath', 'Patna', 'Bihar', '800005', 25.6172, 85.1629],
            [$neet, 'DEMO-JAI-101', 'Demo Medical Campus Hall (Demo)', 'JLN Marg', 'Jaipur', 'Rajasthan', '302004', 26.9064, 75.8189],
            [$jee, 'DEMO-BLR-101', 'Demo Central College (Demo)', 'Palace Road', 'Bengaluru', 'Karnataka', '560001', 12.9822, 77.5921],
            [$neet, 'DEMO-MUM-101', 'Demo University Hall (Demo)', 'Kalina, Santacruz East', 'Mumbai', 'Maharashtra', '400098', 19.0735, 72.8595],
            [$jee, 'DEMO-KOL-101', 'Demo Engineering Institute (Demo)', 'Salt Lake Sector V', 'Kolkata', 'West Bengal', '700091', 22.5726, 88.4339],
            // Intentionally has no coordinates, to exercise the "we do not guess locations" path.
            [$ssc, 'DEMO-NOC-101', 'Demo Centre Without Coordinates (Demo)', 'Station Road', 'Nagpur', 'Maharashtra', null, null, null],
        ];

        foreach ($centres as $row) {
            [$exam, $code, $name, $address, $city, $state, $pin, $lat, $lng] = $row;

            if (! $exam) {
                continue;
            }

            $centre = ExamCentre::updateOrCreate(
                ['centre_code' => $code],
                [
                    'examination_id' => $exam->id,
                    'exam_session_id' => $exam->is($jee) ? $session?->id : null,
                    'name' => $name,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'pin_code' => $pin,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'verification_status' => DataStatus::Unverified->value,
                    'data_status' => DataStatus::Unverified->value,
                    'source_name' => 'DEMO SEED DATA — replace with a verified import before launch',
                    'error_or_limitation' => 'Demonstration record. Not a real examination centre.',
                    'is_demo' => true,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ],
            );

            if ($lat === null) {
                continue;
            }

            $listings = [
                ['Demo Budget Stay near '.$city.' centre', 'hotel', 'Near the main railway station (demo listing)', $lat + 0.008, $lng + 0.008, 899, 128, 4.1],
                ['Demo Breakfast Point '.$city, 'breakfast', 'Main market road (demo listing)', $lat + 0.003, $lng - 0.004, 80, 86, 4.3],
                ['Demo Stationery & Print Shop '.$city, 'stationery', 'Opposite the bus stand (demo listing)', $lat - 0.004, $lng + 0.002, null, 42, 4.0],
                ['Demo Waiting Lounge '.$city, 'waiting_lounge', 'Near platform 1 (demo listing)', $lat + 0.001, $lng + 0.001, 150, 24, 3.8],
            ];

            foreach ($listings as [$pname, $category, $paddress, $plat, $plng, $price, $reviews, $rating]) {
                LocalService::updateOrCreate(
                    ['name' => $pname],
                    [
                        'exam_centre_id' => $centre->id,
                        'category' => $category,
                        'city' => $city,
                        'address' => $paddress,
                        'latitude' => $plat,
                        'longitude' => $plng,
                        'rating' => $rating,
                        'reviews_count' => $reviews,
                        'price_from' => $price,
                        'source' => 'demo_seed',
                        'source_name' => 'DEMO SEED DATA',
                        'source_type' => 'demo',
                        'data_status' => DataStatus::Unverified->value,
                        'is_demo' => true,
                        'last_verified_at' => now(),
                        'error_or_limitation' => 'Demonstration listing — not a real business.',
                        'status' => 'active',
                    ],
                );
            }
        }

        $this->command?->info(sprintf('Seeded %d demo centres (all flagged is_demo) and their demo listings.', count($centres)));
    }
}
