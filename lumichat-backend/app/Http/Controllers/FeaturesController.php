<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeaturesController extends Controller
{
    public function enableAppointment(Request $request)
    {
        $user = Auth::user();

        if (!($user->appointments_enabled ?? false)) {
            $user->forceFill(['appointments_enabled' => true])->save();
        }

        return redirect()->route('appointment.index');
    }
}
