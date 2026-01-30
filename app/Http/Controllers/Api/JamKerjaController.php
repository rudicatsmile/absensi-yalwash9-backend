<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JamKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JamKerjaController extends Controller
{
    public function index(Request $request)
    {
        $query = JamKerja::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $jamKerjas = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $jamKerjas,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_cross_day' => 'boolean',
            'grace_period_minutes' => 'integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jamKerja = JamKerja::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Jam Kerja created successfully',
            'data' => $jamKerja,
        ], 201);
    }

    public function show($id)
    {
        $jamKerja = JamKerja::find($id);

        if (!$jamKerja) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jam Kerja not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $jamKerja,
        ]);
    }

    public function update(Request $request, $id)
    {
        $jamKerja = JamKerja::find($id);

        if (!$jamKerja) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jam Kerja not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'is_cross_day' => 'boolean',
            'grace_period_minutes' => 'integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jamKerja->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Jam Kerja updated successfully',
            'data' => $jamKerja,
        ]);
    }

    public function destroy($id)
    {
        $jamKerja = JamKerja::find($id);

        if (!$jamKerja) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jam Kerja not found',
            ], 404);
        }

        $jamKerja->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Jam Kerja deleted successfully',
        ]);
    }
}
