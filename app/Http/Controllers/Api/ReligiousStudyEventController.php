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
            $validated = $request->validate([
                'cancelled' => ['nullable'],
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d'],
                'page' => ['nullable', 'integer', 'min:1'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
                'sort' => ['nullable', 'in:asc,desc'],
                'search' => ['nullable', 'string', 'max:255'],
            ]);

            $cancelledRaw = $request->query('cancelled', 'false');
            $cancelledBool = filter_var($cancelledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($cancelledBool === null) {
                $cancelledBool = false;
            }

            $page = (int) ($validated['page'] ?? 1);
            $limit = (int) ($validated['limit'] ?? 10);
            $sort = $validated['sort'] ?? 'asc';
            $search = $validated['search'] ?? null;
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;

            $query = ReligiousStudyEvent::query()
                ->where('cancelled', $cancelledBool ? 1 : 0);

            if ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }
            if ($startDate) {
                $query->whereDate('event_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('event_at', '<=', $endDate);
            }

            $query->orderBy('event_at', $sort === 'desc' ? 'desc' : 'asc');

            $paginator = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'params' => [
                    'cancelled' => $cancelledBool,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'sort' => $sort,
                    'search' => $search,
                ],
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
