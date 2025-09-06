<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class FeaturesController extends Controller
{
    /**
     * Signed link target. When visited:
     *  - enables the Appointment nav for this session
     *  - redirects the user straight to /appointment
     */
    public function enableAppointment(Request $request): RedirectResponse
    {
        // The `signed` middleware in web.php already validates the signature.
        // Show the Appointment item for the rest of this session:
        session(['show_appointment_nav' => true]);

        // Optional: keep it for the very next request too (useful if you flash messages)
        // $request->session()->reflash();

        // Go to the booking page
        return redirect()
            ->route('appointment.index')
            ->with('status', 'appointment-enabled');
    }
}
