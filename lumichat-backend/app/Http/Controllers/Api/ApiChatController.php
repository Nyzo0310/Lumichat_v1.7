<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiChatController extends Controller
{
    public function handleMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $rasaUrl = 'http://localhost:5005/webhooks/rest/webhook';

        try {
            $response = Http::post($rasaUrl, [
                'sender' => $request->user()->id,
                'message' => $request->message,
            ]);

            $botMessages = collect($response->json())
                            ->pluck('text')
                            ->filter()
                            ->values();

            return response()->json([
                'bot_reply' => $botMessages
            ]);
        } catch (\Throwable $e) {
            Log::error('Error sending message to Rasa:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
}
