<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanJourneyRequest;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\TripRequest;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\Itinerary;
use App\Services\JourneyPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function index(Request $request)
    {
        $centre = $request->filled('centre_id')
            ? ExamCentre::query()->with('examination:id,name')->find($request->integer('centre_id'))
            : null;

        return view('journey.index', [
            'centres' => ExamCentre::query()->active()
                ->with('examination:id,name')
                ->orderByRaw('LOWER(city) asc')
                ->orderByRaw('LOWER(name) asc')
                ->get(),
            'cities' => CityDirectory::names(),
            'selectedCentre' => $centre,
            'examinations' => Examination::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function plan(PlanJourneyRequest $request, JourneyPlannerService $planner)
    {
        $data = $request->validated();
        $centre = ExamCentre::query()->with('examination:id,name')->findOrFail($data['centre_id']);

        $plan = $planner->plan($centre, [
            'origin_city' => $data['origin_city'],
            'origin_address' => $data['origin_address'] ?? null,
            'origin_lat' => $data['origin_lat'] ?? null,
            'origin_lng' => $data['origin_lng'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'modes' => $data['modes'],
        ]);

        $saved = false;

        if ($request->boolean('save') && $request->user() && $plan->hasOptions()) {
            $itinerary = Itinerary::create([
                'user_id' => $request->user()->id,
                'exam_centre_id' => $centre->id,
                'title' => $data['origin_city'].' → '.$centre->city.' ('.$centre->name.')',
                'origin_city' => $data['origin_city'],
                'origin_lat' => $data['origin_lat'] ?? null,
                'origin_lng' => $data['origin_lng'] ?? null,
                'destination_city' => $centre->city,
                'departure_at' => ! empty($data['departure_date'])
                    ? CarbonImmutable::parse($data['departure_date'], TripRequest::TIMEZONE)->startOfDay()
                    : null,
                'budget' => $data['budget'] ?? null,
                'preferred_modes' => implode(',', $data['modes']),
                'options_snapshot' => $plan->toArray(),
                'data_status' => $plan->isEstimated ? 'estimated' : $plan->intercityStatus,
                'data_limitations' => $plan->limitations === [] ? null : json_encode($plan->limitations),
            ]);

            $saved = true;
            session()->flash('success', __('messages.journey.saved'));
        }

        return view('journey.result', [
            'centre' => $centre,
            'plan' => $plan,
            'origin' => $data['origin_city'],
            'departure' => $data['departure_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'modes' => $data['modes'],
            'saved' => $saved,
            'savedItinerary' => $saved ? ($itinerary ?? null) : null,
            'suggestedRadius' => (int) config('exam_travel.places.default_radius', 3000),
        ]);
    }
}
