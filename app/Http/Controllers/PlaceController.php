<?php

namespace App\Http\Controllers;

use App\Contracts\PlacesProvider;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\PlaceResult;
use App\Models\ExamCentre;
use App\Models\LocalService;
use App\Services\SearchLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{
    public function __construct(private readonly SearchLogger $logger) {}

    public function index(Request $request, PlacesProvider $places)
    {
        $category = array_key_exists((string) $request->input('category'), config('exam_travel.service_categories'))
            ? (string) $request->input('category')
            : null;

        $centre = null;
        $originLat = null;
        $originLng = null;
        $originNote = null;

        if ($request->filled('centre_id')) {
            $centre = ExamCentre::query()->find($request->integer('centre_id'));

            if ($centre?->hasCoordinates()) {
                $originLat = (float) $centre->latitude;
                $originLng = (float) $centre->longitude;
                $originNote = 'Distances are measured from '.$centre->name.' (verified coordinates).';
            } elseif ($centre) {
                $originNote = 'This centre has no verified coordinates yet, so we cannot show distances — we do not guess them.';
            }
        } elseif ($request->filled('city')) {
            $coords = CityDirectory::coords((string) $request->input('city'));
            if ($coords) {
                $originLat = $coords[0];
                $originLng = $coords[1];
                $originNote = 'Distances are measured from the city reference point for '.$request->input('city').'.';
            }
        }

        $radius = in_array($request->integer('radius'), config('exam_travel.places.radius_options'), true)
            ? $request->integer('radius')
            : (int) config('exam_travel.places.default_radius', 3000);

        $result = $places->search([
            'lat' => $originLat,
            'lng' => $originLng,
            'city' => $request->input('city'),
            'category' => $category,
            'keyword' => $request->input('q'),
            'radius_m' => $radius,
            'limit' => 18,
            'exam_centre_id' => $centre?->id,
        ]);

        $this->logger->log('place', [
            'category' => $category,
            'city' => $request->input('city'),
            'centre_id' => $centre?->id,
            'radius' => $radius,
        ], is_array($result->data) ? count($result->data) : 0, $request->user()?->id);

        return view('places.index', [
            'places' => $result->isAvailable() ? $result->data : [],
            'result' => $result,
            'centre' => $centre,
            'originNote' => $originNote,
            'category' => $category,
            'radius' => $radius,
            'categories' => config('exam_travel.service_categories'),
            'radiusOptions' => config('exam_travel.places.radius_options'),
            'centres' => ExamCentre::query()->active()
                ->select('id', 'name', 'city', 'centre_code')
                ->orderByRaw('LOWER(city) asc')
                ->orderByRaw('LOWER(name) asc')
                ->limit(300)
                ->get(),
            'saved' => $this->savedIds($request),
            'providerName' => $places->name(),
            'usesLiveProvider' => $places->name() === 'google',
        ]);
    }

    public function save(Request $request, LocalService $place)
    {
        abort_unless($place->status === 'active', 404);

        DB::table('saved_places')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'local_service_id' => $place->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', __('messages.places.saved'));
    }

    public function unsave(Request $request, LocalService $place)
    {
        DB::table('saved_places')
            ->where('user_id', $request->user()->id)
            ->where('local_service_id', $place->id)
            ->delete();

        return back()->with('success', __('messages.places.unsaved'));
    }

    public function saved(Request $request)
    {
        $places = LocalService::query()
            ->whereIn('id', function ($q) use ($request) {
                $q->select('local_service_id')->from('saved_places')->where('user_id', $request->user()->id);
            })
            ->with('centre:id,name,city')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (LocalService $m) => PlaceResult::fromModel($m))
            ->all();

        return view('places.saved', [
            'places' => $places,
            'categories' => config('exam_travel.service_categories'),
        ]);
    }

    /** @return array<int,int> */
    private function savedIds(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return DB::table('saved_places')
            ->where('user_id', $request->user()->id)
            ->pluck('local_service_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
