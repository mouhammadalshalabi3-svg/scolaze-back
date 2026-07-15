<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Announcement;

class DashboardApiController extends Controller
{
    public function overview()
    {
        return response()->json([
            'total_students'   => Student::count(),
            'active_teachers'  => Teacher::count(),
            'ads_this_month'   => Announcement::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count(),
        ]);
    }
}