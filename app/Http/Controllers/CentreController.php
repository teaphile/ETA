<?php

namespace App\Http\Controllers;

use App\Contracts\PlacesProvider;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Services\CentreSearchService;
use Illuminate\Http\Request;

class CentreController extends Controller
{
    public function __construct(private readonly CentreSearchService $search) {}

    public function index(Request $request)
    {
        $filters = $this->search->normalise($request->only(['exam', 'session', 'state', 'city', 'code', 'q', 'sort']));
        $centres = $this->search->query($filters);

        $this->search->log($filters, $centres->total(), $request->user()?->id);

        return view('centres.index', [
            'centres' => $centres,
            'filters' => $filters,
            'hasFilters' => collect($filters)->except('sort')->filter()->isNotEmpty(),
            'examinations' => Examination::query()->active()
                ->withCount(['centres' => fn ($q) => $q->where('status', 'active')])
                ->orderBy('name')->get(),
            'states' => $this->search->availableStates(),
            'cities' => $this->search->availableCities(),
        ]);
    }

    public function show(Request $request, ExamCentre $centre, PlacesProvider $places)
    {
        abort_unless($centre->status === 'active', 404);

        $centre->load(['examination:id,name,official_url', 'session:id,name,year']);

        // Nearby services near the centre itself. When the centre has no verified
        // coordinates the provider reports unavailable and the UI says so.
        $result = $places->search([
            'lat' => $centre->hasCoordinates() ? (float) $centre->latitude : null,
            'lng' => $centre->hasCoordinates() ? (float) $centre->longitude : null,
            'city' => $centre->city,
            'limit' => 6,
            'radius_m' => (int) config('exam_travel.places.default_radius', 3000),
            'exam_centre_id' => $centre->id,
        ]);

        return view('centres.show', [
            'centre' => $centre,
            'nearby' => $result->isAvailable() ? $result->data : [],
            'nearbyResult' => $result,
        ]);
    }
}
