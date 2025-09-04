<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $settings = \App\Models\UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        // 👇 point to resources/views/settings.blade.php
        return view('settings', compact('settings', 'user'));
    }
    
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email_reminders' => ['nullable','boolean'],
            'sms_alerts'      => ['nullable','boolean'],
            'autosave_chats'  => ['nullable','boolean'],
            'autodelete_days' => ['nullable','integer','min:0','max:365'],
            'dark_mode'       => ['nullable','boolean'],
        ]);

        // checkboxes: present => "on", absent => null
        $normalized = [
            'email_reminders' => (bool) $request->boolean('email_reminders'),
            'sms_alerts'      => (bool) $request->boolean('sms_alerts'),
            'autosave_chats'  => (bool) $request->boolean('autosave_chats'),
            'dark_mode'       => (bool) $request->boolean('dark_mode'),
            'autodelete_days' => $request->filled('autodelete_days') ? (int) $request->input('autodelete_days') : null,
        ];

        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
        $settings->update($normalized);

        return back()->with('success', 'Settings saved.');
    }
}
