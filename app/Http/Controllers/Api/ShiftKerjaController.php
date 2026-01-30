<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftKerja;
use Illuminate\Http\Request;

class ShiftKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ShiftKerja::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $shifts = $query->orderBy('start_time')->get();

        return response()->json([
            'status' => 'success',
            'data' => $shifts,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $shift = ShiftKerja::find($id);

        if (!$shift) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shift not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $shift,
        ]);
    }
}
