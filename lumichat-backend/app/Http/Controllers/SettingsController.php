<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        return view('settings', compact('settings', 'user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // Only the kept fields
        $data = $request->validate([
            'autodelete_days' => ['nullable','integer','min:0','max:365'],
            'dark_mode'       => ['nullable','boolean'],
        ]);

        $normalized = [
            'dark_mode'       => (bool) $request->boolean('dark_mode'),
            'autodelete_days' => $request->filled('autodelete_days')
                ? (int) $request->input('autodelete_days')
                : null, // blank disables cleanup
        ];

        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
        $settings->update($normalized);

        return back()->with('success', 'Settings saved.');
    }
}
