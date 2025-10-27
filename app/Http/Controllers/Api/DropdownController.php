<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DropdownController extends Controller
{
    public function shiftKerjas(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        // Error jika parameter user_id tidak tersedia
        if ($userId === null || $userId === '') {
            return response()->json([
                'error' => 'Parameter user_id wajib disediakan.',
            ], 400);
        }

        // Opsional: validasi tipe user_id numerik
        if (! is_numeric($userId)) {
            return response()->json([
                'error' => 'Parameter user_id harus berupa angka.',
            ], 422);
        }

        // Query join pivot -> shift_kerjas dan filter user_id
        $items = DB::table('shift_kerjas')
            ->join('shift_kerja_user', 'shift_kerja_user.shift_kerja_id', '=', 'shift_kerjas.id')
            ->where('shift_kerja_user.user_id', (int) $userId)
            ->select('shift_kerjas.*')
            ->distinct()
            ->get();

        // Error jika tidak ada data untuk user_id
        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan untuk user_id yang diberikan.',
                'data' => [],
            ], 404);
        }

        // Sukses
        return response()->json([
            'data' => $items,
        ], 200);
    }
}