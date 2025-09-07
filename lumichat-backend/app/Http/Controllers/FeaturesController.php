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
    public function enableAppointment(Request $request)
    {
        // Flash success once and redirect to booking form
        return redirect()
            ->route('appointment.create')
            ->with('success', 'Appointment booking enabled'); // <-- use 'success', not 'status'
    }
}
