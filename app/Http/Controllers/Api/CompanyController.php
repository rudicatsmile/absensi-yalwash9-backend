<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class CompanyController extends Controller
{
    //show
    public function show()
    {
        $company = Company::find(1);
        return response(['company' => $company], 200);
    }

    public function dropdownLocations(Request $request)
    {
        $userId = $request->query('user_id');

        if (empty($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter user_id tidak tersedia',
            ], 422);
        }

        $locations = DB::table('company_location_user')
            ->join('company_locations', 'company_location_user.company_location_id', '=', 'company_locations.id')
            ->where('company_location_user.user_id', $userId)
            ->select('company_locations.*')
            ->get();

        if ($locations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan untuk user_id tertentu',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $locations,
        ], 200);
    }
}
