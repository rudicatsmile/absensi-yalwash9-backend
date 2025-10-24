<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingType;

class MeetingTypeController extends Controller
{
    public function getMeetingTypes()
    {
        try {
            $types = MeetingType::all();

            if ($types->isEmpty()) {
                return response()->json([
                    'message' => 'Data tidak ditemukan',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'message' => 'Meeting types retrieved successfully',
                'data' => $types,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal mengambil data meeting types',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}