<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\User;
use App\Services\NotificationService;
class AnnoucementApiController extends Controller
{
    
    // كل الإعلانات مصنفة حسب السنة
    public function index()
    {
        $ads = Announcement::with('user')->get()->groupBy('target_role')->map(fn($group) 
        => $group->map(fn($ad) => [
                'ad_id'   => $ad->id,
                'title'   => $ad->title,
                'content' => $ad->content,
                'created_at' => $ad->created_at,
            ]));

        return response()->json(['ads' => $ads]);
    }
   
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string',
            'content'     => 'required|string',
            'target_role' => 'required|in:all,teachers,first_grade,second_grade,third_grade',
            'image'       => 'nullable|image|max:2048',
            'type'        => 'required|in:normal,urgent,important,activity,rule_policy',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ads', 'public');
        }

       $ad = Announcement::create([
    'title'       => $request->title,
    'content'     => $request->content,
    'target_role' => $request->target_role,
    'user_id'     => auth()->id(),
    'image'       => $imagePath, 
    'type'        => $request->type,
]);
$userIds = $this->resolveTargetUserIds($request->target_role);

         NotificationService::sendToMany($userIds, $ad->title, $ad->content, 'announcement', $imagePath);
        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح',
            'ad'      => $ad,
        ], 201);
    }
    private function resolveTargetUserIds(string $targetRole)
{
    if ($targetRole === 'all') {
        return User::pluck('id');
    }

    if ($targetRole === 'teachers') {
        return User::where('role', 'teacher')->pluck('id');
    }

    // first_grade / second_grade / third_grade
    $gradeIndex = ['first_grade' => 0, 'second_grade' => 1, 'third_grade' => 2][$targetRole];

    $gradeId = \App\Models\Grade::orderBy('id')->skip($gradeIndex)->value('id');

    if (!$gradeId) {
        return collect();
    }

    return \App\Models\Student::whereHas('section', function ($q) use ($gradeId) {
        $q->where('grade_id', $gradeId);
    })->with('user')->get()->pluck('user_id');
}
public function destroy($id)
{
    $announcement = \App\Models\Announcement::findOrFail($id);
    $announcement->delete();

    return response()->json(['message' => 'تم حذف الإعلان بنجاح']);
}
}
