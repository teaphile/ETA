<?php

namespace Database\Seeders;

use App\Models\Examination;
use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reference content that is safe in any environment.
 *
 * These are examinations and help articles — not examination-centre records. Official
 * centre lists are never seeded; they must be imported by an administrator from a
 * verified source. Official URLs point at the authority's own website so a student can
 * always cross-check anything shown here.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $examinations = [
            ['name' => 'JEE Main', 'conducting_authority' => 'National Testing Agency', 'official_url' => 'https://jeemain.nta.nic.in'],
            ['name' => 'NEET UG', 'conducting_authority' => 'National Testing Agency', 'official_url' => 'https://neet.nta.nic.in'],
            ['name' => 'CUET UG', 'conducting_authority' => 'National Testing Agency', 'official_url' => 'https://cuet.nta.nic.in'],
            ['name' => 'SSC CGL', 'conducting_authority' => 'Staff Selection Commission', 'official_url' => 'https://ssc.gov.in'],
            ['name' => 'SSC CHSL', 'conducting_authority' => 'Staff Selection Commission', 'official_url' => 'https://ssc.gov.in'],
            ['name' => 'UPSC Civil Services (Preliminary)', 'conducting_authority' => 'Union Public Service Commission', 'official_url' => 'https://upsc.gov.in'],
            ['name' => 'IBPS PO', 'conducting_authority' => 'Institute of Banking Personnel Selection', 'official_url' => 'https://www.ibps.in'],
            ['name' => 'RRB NTPC', 'conducting_authority' => 'Railway Recruitment Board', 'official_url' => 'https://www.rrbcdg.gov.in'],
        ];

        foreach ($examinations as $exam) {
            Examination::updateOrCreate(
                ['slug' => Str::slug($exam['name'])],
                $exam + ['slug' => Str::slug($exam['name']), 'status' => 'active'],
            );
        }

        $faqs = [
            [
                'q' => 'How do I confirm my actual exam centre?',
                'a' => "Only your official admit card (or city-intimation slip) from the examination authority confirms your allotted centre. This platform helps you plan travel to a centre; it never allots one and never decides which centre is yours.\n\nDownload your admit card from the official website and cross-check the centre code, name and address printed on it.",
                'c' => 'Exam Centre',
                'u' => '',
            ],
            [
                'q' => 'I cannot find my centre in the search. What should I do?',
                'a' => "Our database only contains centres that an administrator has imported from a verified or officially obtained list, so a missing centre means it has not been added yet — not that it does not exist.\n\nUse the address printed on your admit card in your maps app, and tell an administrator so the verified list can be imported. We deliberately do not invent a centre list.",
                'c' => 'Exam Centre',
                'u' => '',
            ],
            [
                'q' => 'How early should I reach the exam centre?',
                'a' => "The reporting time printed on your admit card always overrides general advice. As a planning rule, aim to reach the centre area about 60–90 minutes before the reporting time so that traffic, frisking and finding your seating block do not create panic.\n\nIf you arrive in the city the day before, rehearse the last leg from your stay to the centre once.",
                'c' => 'Exam Day',
                'u' => '',
            ],
            [
                'q' => 'What documents should I carry to the centre?',
                'a' => "Typically a printed admit card, the original photo ID specified in your examination notification, and passport-size photographs if the notification asks for them. The exact list is exam-specific.\n\nYour admit card and the official notification are the only authoritative sources for this — check them, not a community post.",
                'c' => 'Exam Day',
                'u' => '',
            ],
            [
                'q' => 'Where do I book train tickets, and can I book them here?',
                'a' => "Book only on IRCTC (the official Indian Railways portal) or at an authorised counter or agent. This platform does not sell tickets, does not hold inventory, and cannot show live seat availability.\n\nCheck PNR status and train running status on NTES/IRCTC before you leave for the station.",
                'c' => 'Travel Booking',
                'u' => 'https://www.irctc.co.in',
            ],
            [
                'q' => 'Are the prices shown on this platform live fares?',
                'a' => "No. Prices labelled \"estimated\" are disclosed distance-based planning ranges: the calculation method is printed next to each one. Flight prices are shown as \"Price unavailable\" because air fares change dynamically and no permitted public API publishes them.\n\nAlways confirm the real fare on the official booking website.",
                'c' => 'Travel Booking',
                'u' => '',
            ],
            [
                'q' => 'Why does the planner say my city is not in the directory?',
                'a' => "The planner needs coordinates for your starting point to calculate a distance-based estimate. We hold a reference list of well-known Indian cities and we will not guess coordinates for anything else.\n\nPick a listed city, share your location, or type a fuller starting address so the address can be resolved through the configured geocoder.",
                'c' => 'Travel Booking',
                'u' => '',
            ],
            [
                'q' => 'How do I stay safe in an unfamiliar exam city?',
                'a' => "Practical steps students commonly use: book accommodation near the centre or a well-connected station area; share your live location with a family member; save the centre location offline; carry a power bank; keep cash for autos; and avoid posting your phone number or exact stay publicly.\n\nA provider rating on a listing is other people's opinion — it is not a safety assessment by us.",
                'c' => 'Safety',
                'u' => '',
            ],
            [
                'q' => 'What data does this platform store about me?',
                'a' => "Your name, email, optional phone and city, your saved itineraries, saved places, and (for signed-in users) chat transcripts kept for a limited period.\n\nWe do not collect your precise home address, we do not record continuous device location, and we do not sell data. See the privacy notice for the retention periods and how to request deletion.",
                'c' => 'About This Platform',
                'u' => '/privacy',
            ],
            [
                'q' => 'Is this platform official or government-affiliated?',
                'a' => "No. Exam Travel Assist is an independent student help platform. It is not affiliated with NTA, SSC, UPSC, any examination authority, Indian Railways, IRCTC, or any transport operator or government body.\n\nAlways rely on official sources for examination instructions, centre allotment and bookings.",
                'c' => 'About This Platform',
                'u' => '',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['q']],
                [
                    'answer' => $faq['a'],
                    'category' => $faq['c'],
                    'source_url' => $faq['u'] !== '' ? $faq['u'] : null,
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
