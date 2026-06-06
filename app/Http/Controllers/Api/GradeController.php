<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grade;
class GradeController extends Controller
{
    public function store(Request $request)
{
    $grade = Grade::create([
        'name' => $request->name
    ]);

    return response()->json($grade, 201);
}
}
