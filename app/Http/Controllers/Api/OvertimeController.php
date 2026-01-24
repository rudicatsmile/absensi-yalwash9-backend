<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Attendance;
use App\Models\MeetingType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    public function approve(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:overtimes,id',
                'shift_id' => 'nullable|exists:shift_kerjas,id', // Optional shift_id
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
            
            DB::beginTransaction();

            $overtime = Overtime::with('user')->findOrFail($validated['id']);

            if ($overtime->status !== 'pending') {
                return response()->json([
                    'message' => 'Overtime request has already been processed',
                ], 400);
            }

            // Update overtime status
            $overtime->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Insert into Attendance
            // Follow pattern from PermitController
            $attendanceData = [
                'user_id' => $overtime->user_id,
                'shift_id' => $validated['shift_id'] ?? null, // Use provided shift_id or null
                'company_location_id' => 1, // Default Gedung A
                'departemen_id' => $overtime->user->departemen_id,
                'date' => $overtime->date->format('Y-m-d'),
                'time_in' => $overtime->start_time ? $overtime->start_time->format('H:i:s') : null,
                'time_out' => $overtime->end_time ? $overtime->end_time->format('H:i:s') : null,
                'latlon_in' => $this->getGeoLocation($request),
                'latlon_out' => $this->getGeoLocation($request),
                'status' => 'on_time', // As per pattern
                'is_overtime' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Attendance::create($attendanceData);

            DB::commit();

            return response()->json([
                'message' => 'Overtime request approved and attendance recorded successfully',
                'data' => $overtime,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve overtime request',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan lokasi GPS (Latitude, Longitude) dari request atau fallback.
     *
     * Fungsi ini mencoba mengambil koordinat dari input client.
     * Jika gagal (tidak didukung/izin ditolak/sinyal hilang), gunakan lokasi default perusahaan.
     *
     * @param Request $request
     * @return string Format "latitude,longitude"
     */
    private function getGeoLocation(Request $request)
    {
        // 1. Coba ambil dari input request (GPS perangkat)
        $latlon = $request->input('latlon_in');

        // 2. Validasi format latitude,longitude
        if ($latlon && preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $latlon)) {
            return $latlon;
        }

        // 3. Fallback: Gunakan lokasi default perusahaan (Gedung A / ID 1)
        // Ini menangani kasus: Perangkat tidak mendukung GPS, Izin lokasi ditolak, atau Sinyal GPS hilang
        try {
            $defaultLocation = \App\Models\CompanyLocation::find(1);
            if ($defaultLocation) {
                return "{$defaultLocation->latitude},{$defaultLocation->longitude}";
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        // 4. Default hardcoded jika database gagal
        return '-6.1914783,106.9372911';
    }

    public function startOvertime(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $validator = Validator::make($request->all(), [
                'meeting_type_id' => 'nullable|exists:meeting_types,id',
                'notes' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:255',
                'start_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Semua kolom harus diisi.', 'errors' => $validator->errors()], 422);
            }
            $now = Carbon::now();
            // $now = Carbon::now()->addHour(9);
            $startTime = $now->format('H:i'); // contoh: 17:05
            $date = $now->toDateString(); // contoh: 2025-08-07
            Log::info('Start Overtime', [
                'user_id' => $user->id,
                'start_time' => $startTime,
                'date' => $date,
            ]);

            // Cek apakah sudah ada lembur yang sedang berlangsung
            $existingOvertime = Overtime::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->first();

            if ($existingOvertime) {
                return response()->json(['message' => 'Anda sudah memulai lembur hari ini.'], 422);
            }
            $documentPath = null;
            if ($request->hasFile('start_document_path')) {
                $document = $request->file('start_document_path');
                $documentPath = $document->store('overtime_documents', 'public');
            }

            Overtime::create([
                'user_id' => $user->id,
                'meeting_type_id' => $request->meeting_type_id,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $startTime,       // default same as start_time
                'reason' => $request->reason,
                'status' => 'pending',
                'document' => $documentPath,
                'notes' => $request->notes,
            ]);

            return response()->json(['message' => 'Lembur berhasil dimulai'], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal memulai lembur', 'error' => $th->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $query = Overtime::where('user_id', $user->id);

            // Filter by month (format: YYYY-MM)
            if ($request->has('month')) {
                try {
                    [$year, $month] = explode('-', $request->month);
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Format bulan tidak valid. Gunakan YYYY-MM.'], 422);
                }
            }

            // Muat relasi agar 'meetingType' (id, name) tersedia dalam response
            // $query->with(['meetingType:id,name']);

            // Tambahkan kolom datar 'meeting_type_name' hasil subquery ke meeting_types.name
            $query->addSelect([
                'meeting_type_name' => MeetingType::select('name')
                    ->whereColumn('overtimes.meeting_type_id', 'meeting_types.id')
                    ->limit(1),
            ]);
            $overtimes = $query->orderBy('date', 'desc')->get();

            return response()->json(['data' => $overtimes, 'message' => 'Daftar lembur'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal Mendapatkan Data lembur', 'error' => $th->getMessage()], 500);
        }
    }

    public function endOvertime(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:overtimes,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Semua kolom harus diisi.', 'errors' => $validator->errors()], 422);
            }
            $data = $validator->validated();

            $overtime = Overtime::where('id', $data['id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$overtime) {
                return response()->json(['message' => 'Data lembur tidak ditemukan.'], 404);
            }

            if ($overtime->end_time) {
                return response()->json(['message' => 'Data lembur sudah diakhiri.'], 400);
            }
            $now = Carbon::now();
            // $now = Carbon::now()->addHour(12);
            $endTime = $now->format('H:i');

            $overtime->update([
                'end_time' => $endTime,
                'reason' => $request->reason ?? $overtime->reason,
                'status' => 'pending', // biar supervisor bisa review
            ]);

            return response()->json(['data' => $overtime, 'message' => 'Lembur berhasil diselesaikan dan menunggu persetujuan'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal Menyelesaikan lembur', 'error' => $th->getMessage()], 500);
        }
    }

    public function checkTodayOvertimeStatus()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $today = now('Asia/Jakarta');

            // Cek status lembur
            $overtime = Overtime::where('user_id', $user->id)
                ->whereDate('date', $today->toDateString())
                ->latest()
                ->first();

            if (!$overtime) {
                return response()->json([
                    'status' => 'not_started',
                    'message' => 'Belum ada lembur hari ini',
                    'data' => null,
                ], 200);
            }

            if ($overtime->start_time && !$overtime->end_time) {
                return response()->json([
                    'status' => 'in_progress',
                    'message' => 'Lembur sedang berlangsung',
                    'data' => $overtime,
                ], 200);
            }

            if ($overtime->start_time && $overtime->end_time) {
                return response()->json([
                    'status' => 'completed',
                    'message' => 'Lembur telah selesai, menunggu persetujuan',
                    'data' => $overtime,
                ], 200);
            }

            return response()->json([
                'status' => 'not_started',
                'message' => 'Status lembur tidak diketahui',
                'data' => $overtime,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal memeriksa status lembur', 'error' => $th->getMessage()], 500);
        }
    }
}