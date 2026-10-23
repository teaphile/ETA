<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Switch the interface language for the current session (and profile, if signed in). */
    public function update(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        $request->session()->put('locale', $locale);

        if ($user = $request->user()) {
            $user->forceFill(['preferred_language' => $locale])->save();
        }

        app()->setLocale($locale);

        return back();
    }
}
