<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReligiousStudyEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function detail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Input tidak valid',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $id = (int) $request->input('id');
            $event = ReligiousStudyEvent::find($id);

            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $event,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('api:religious-study-events.detail.failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Kesalahan server'
            ], 500);
        }
    }
}
