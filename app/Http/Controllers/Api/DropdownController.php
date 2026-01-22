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
        if (!is_numeric($userId)) {
            return response()->json([
                'error' => 'Parameter user_id harus berupa angka.',
            ], 422);
        }

        // Query join pivot -> shift_kerjas dan filter user_id
        /*
        $items = DB::table('shift_kerjas')
            ->join('shift_kerja_user', 'shift_kerja_user.shift_kerja_id', '=', 'shift_kerjas.id')
            ->where('shift_kerja_user.user_id', (int) $userId)
            ->select('shift_kerjas.*')
            ->distinct()
            ->get();
        */


        //Bila hari ini adalah bukan hari kamis, jam 17:00  sampai 20:00 maka tidak boleh memilih shift_kerja_id = 5
        $hariIni = date('N'); // 1 = Senin, 2 = Selasa, dst.
        $jamSekarang = date('H:i');

        //Untuk pengajian malam Jumat. Jika Hari Kamis, jam 5 sore sampai jam 7 malam maka tampilkan semua daftar shift
        if ($hariIni == 4 && $jamSekarang >= '17:00' && $jamSekarang <= '20:00') {
            $items = DB::table('shift_kerjas')
                ->join('shift_kerja_user', 'shift_kerja_user.shift_kerja_id', '=', 'shift_kerjas.id')
                ->where('shift_kerja_user.user_id', (int) $userId)
                ->select('shift_kerjas.*')
                ->distinct()
                ->get();

        } else {
            $items = DB::table('shift_kerjas')
                ->join('shift_kerja_user', 'shift_kerja_user.shift_kerja_id', '=', 'shift_kerjas.id')
                ->where('shift_kerja_user.user_id', (int) $userId)
                ->where('shift_kerjas.id', '<>', 5)
                ->select('shift_kerjas.*')
                ->distinct()
                ->get();

        }

        //echo $hariIni .' - '.$jamSekarang; exit;

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
