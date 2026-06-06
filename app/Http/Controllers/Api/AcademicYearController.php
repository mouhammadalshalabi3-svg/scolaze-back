<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
class AcademicYearController extends Controller
{
      public function store(Request $request)
    {
        $academicYear = AcademicYear::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        return response()->json($academicYear, 201);
    }
}
