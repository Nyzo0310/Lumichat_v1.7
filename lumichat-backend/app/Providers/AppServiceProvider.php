<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\Appointment;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // when a user registers
        User::created(function (User $user) {
            ActivityLog::create([
                'event'        => 'user.registered',
                'description'  => "New user registered: {$user->name}",
                'actor_id'     => $user->id,
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'meta'         => ['email' => $user->email, 'role' => $user->role ?? null],
            ]);
        });

        // when a chat session is created
        ChatSession::created(function (ChatSession $session) {
            ActivityLog::create([
                'event'        => 'chat_session.started',
                'description'  => 'Chat session started: ' . Str::limit($session->topic_summary ?: 'New chat session', 80),
                'actor_id'     => $session->user_id,
                'subject_type' => ChatSession::class,
                'subject_id'   => $session->id,
                'meta'         => ['user_id' => $session->user_id],
            ]);
        });

        // when an appointment is created
        Appointment::created(function (Appointment $appt) {
            ActivityLog::create([
                'event'        => 'appointment.created',
                'description'  => 'Appointment created',
                'actor_id'     => $appt->student_id,
                'subject_type' => Appointment::class,
                'subject_id'   => $appt->id,
                'meta'         => [
                    'student_id'   => $appt->student_id,
                    'counselor_id' => $appt->counselor_id,
                    'scheduled_at' => optional($appt->scheduled_at)->toIso8601String(),
                ],
            ]);
        });
    }
}
