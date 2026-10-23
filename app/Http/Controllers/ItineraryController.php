<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    public function index(Request $request)
    {
        return view('itineraries.index', [
            'items' => $request->user()->itineraries()
                ->with('centre:id,name,city,centre_code')
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Itinerary $itinerary)
    {
        // Ownership is enforced on the server: a student can never read another
        // student's saved plan by guessing an identifier.
        abort_unless($itinerary->isOwnedBy($request->user()), 403);

        $itinerary->load('centre.examination:id,name');

        return view('itineraries.show', ['itinerary' => $itinerary]);
    }

    public function destroy(Request $request, Itinerary $itinerary)
    {
        abort_unless($itinerary->isOwnedBy($request->user()), 403);

        $itinerary->delete();

        return redirect()->route('itineraries.index')->with('success', 'Itinerary deleted.');
    }
}
