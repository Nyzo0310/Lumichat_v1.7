<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // GET /appointment
    public function index()
    {
        $counselors = Counselor::active()->orderBy('name')->get();

        // even if empty, the variable exists → the view won’t error
        return view('appointment.index', compact('counselors'));
    }
    // POST /appointment
    public function store(Request $request)
    {
        // validate & create appointment (counselor exists, future datetime, conflict check, etc.)
    }

    // GET /appointment/slots/{counselor}
    public function slots($counselor)
    {
        // return available slots as JSON
    }

    // GET /appointment/history
    public function history()
    {
        return view('appointment.history');
    }

    // GET /appointment/view/{id}
    public function show($id)
    {
        return view('appointment.view', compact('id'));
    }

    // PATCH /appointment/{id}/cancel
    public function cancel($id, Request $request)
    {
        // ownership/role check + cancel logic
    }
}
