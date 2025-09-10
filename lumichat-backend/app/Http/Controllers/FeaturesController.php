<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FeaturesController extends Controller
{
    public function enableAppointment(Request $request)
    {
        $request->validate([
            'expires' => 'sometimes', // signed link usually has extra params; keep flexible
        ]);

        // Session unlock (works immediately)
        session(['appointment_enabled' => true]);

        // Persist if a boolean column exists (optional)
        $user = Auth::user();
        if ($user && Schema::hasColumn('users', 'appointment_enabled')) {
            DB::table('users')->where('id', $user->id)->update([
                'appointment_enabled' => 1,
                'updated_at' => now(),
            ]);
        }

        // Redirect student to the booking form
        return redirect()->route('appointment.create')->with('status', 'Appointment booking unlocked.');
    }
}
