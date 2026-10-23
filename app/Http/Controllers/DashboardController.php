<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard.index', [
            'user' => $user,
            'tripCount' => $user->itineraries()->count(),
            'savedPlaceCount' => DB::table('saved_places')->where('user_id', $user->id)->count(),
            'recent' => $user->itineraries()->with('centre:id,name,city')->latest('id')->limit(3)->get(),
            'hasChatHistory' => ChatConversation::where('user_id', $user->id)->exists(),
        ]);
    }
}
