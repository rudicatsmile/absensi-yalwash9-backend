<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventAttendance;
use App\Models\ReligiousStudyEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function confirmAttendance($id, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response(['message' => 'Unauthorized'], 401);
        }
        $event = ReligiousStudyEvent::find($id);
        if (!$event) {
            return response(['message' => 'Event not found'], 404);
        }
        if ($event->cancelled) {
            return response(['message' => 'Event cancelled'], 422);
        }
        EventAttendance::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['confirmed_at' => now()]
        );
        return response(['message' => 'Confirmed'], 200);
    }
}