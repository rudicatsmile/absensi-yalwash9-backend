<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReligiousStudyEvent;
use Illuminate\Http\Request;

class ReligiousStudyEventController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cancelledRaw = $request->query('cancelled', 0);
            $cancelled = is_numeric($cancelledRaw) ? (int) $cancelledRaw : 0;

            $events = ReligiousStudyEvent::query()
                ->where('cancelled', $cancelled)
                ->orderBy('event_at', 'asc')
                ->get();

            return response()->json([
                'data' => $events,
                'params' => ['cancelled' => $cancelled],
                'message' => 'Daftar event kajian',
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('api:religious-study-events.failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil daftar event'], 500);
        }
    }
}

