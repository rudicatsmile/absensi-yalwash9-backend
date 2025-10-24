<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MeetingController extends Controller
{
    public function startMeeting(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:255',
                'start_document_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Semua kolom harus diisi.', 'errors' => $validator->errors()], 422);
            }

            $now = Carbon::now();
            $startTime = $now->format('H:i');
            $date = $now->toDateString();

            Log::info('Start Meeting', [
                'user_id' => $user->id,
                'start_time' => $startTime,
                'date' => $date,
            ]);

            // Cek apakah sudah ada meeting yang sedang berlangsung
            $existingMeeting = Meeting::where('employee_id', $user->id)
                ->whereDate('date', $date)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->first();

            if ($existingMeeting) {
                return response()->json(['message' => 'Anda sudah memulai meeting hari ini.'], 422);
            }

            $documentPath = null;
            if ($request->hasFile('start_document_path')) {
                $document = $request->file('start_document_path');
                $documentPath = $document->store('meeting_documents', 'public');
            }

            Meeting::create([
                'employee_id' => $user->id,
                'date' => $date,
                'start_time' => $startTime,
                'reason' => $request->reason,
                'status' => 'pending',
                'document' => $documentPath,
                'notes' => $request->notes,
            ]);

            return response()->json(['message' => 'Meeting berhasil dimulai'], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal memulai meeting', 'error' => $th->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $query = Meeting::where('employee_id', $user->id);

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

            $meetings = $query->orderBy('date', 'desc')->get();

            return response()->json(['data' => $meetings, 'message' => 'Daftar meeting'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal Mendapatkan Data meeting', 'error' => $th->getMessage()], 500);
        }
    }

    public function endMeeting(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:meetings,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Semua kolom harus diisi.', 'errors' => $validator->errors()], 422);
            }
            $data = $validator->validated();

            $meeting = Meeting::where('id', $data['id'])
                ->where('employee_id', $user->id)
                ->first();

            if (! $meeting) {
                return response()->json(['message' => 'Data meeting tidak ditemukan.'], 404);
            }

            if ($meeting->end_time) {
                return response()->json(['message' => 'Data meeting sudah diakhiri.'], 400);
            }

            $now = Carbon::now();
            $endTime = $now->format('H:i');

            $meeting->update([
                'end_time' => $endTime,
                'reason' => $request->reason ?? $meeting->reason,
                'status' => 'pending',
            ]);

            return response()->json(['data' => $meeting, 'message' => 'Meeting berhasil diselesaikan dan menunggu persetujuan'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal Menyelesaikan meeting', 'error' => $th->getMessage()], 500);
        }
    }

    public function checkTodayMeetingStatus()
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['message' => 'Pegawai tidak ditemukan.'], 401);
            }

            $today = now('Asia/Jakarta');

            // Cek status meeting
            $meeting = Meeting::where('employee_id', $user->id)
                ->whereDate('date', $today->toDateString())
                ->latest()
                ->first();

            if (! $meeting) {
                return response()->json([
                    'status' => 'not_started',
                    'message' => 'Belum ada meeting hari ini',
                    'data' => null,
                ], 200);
            }

            if ($meeting->start_time && ! $meeting->end_time) {
                return response()->json([
                    'status' => 'in_progress',
                    'message' => 'Meeting sedang berlangsung',
                    'data' => $meeting,
                ], 200);
            }

            if ($meeting->start_time && $meeting->end_time) {
                return response()->json([
                    'status' => 'completed',
                    'message' => 'Meeting telah selesai, menunggu persetujuan',
                    'data' => $meeting,
                ], 200);
            }

            return response()->json([
                'status' => 'not_started',
                'message' => 'Status meeting tidak diketahui',
                'data' => $meeting,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Gagal memeriksa status meeting', 'error' => $th->getMessage()], 500);
        }
    }
}