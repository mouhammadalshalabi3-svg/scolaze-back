<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Grade;
use App\Models\User;
use App\Services\NotificationService;
class AnnoucementApiController extends Controller
{

    // كل الإعلانات — مفلترة حسب دور المستخدم (الأدمن بيشوف الكل)
    public function index()
    {
        $user = auth()->user();
        $ads  = Announcement::with('user')->get();

        if ($user->role !== 'admin') {
            $allowedRoles = collect(['all']);

            if ($user->role === 'teacher') {
                $allowedRoles->push('teachers');
            }

            if ($user->role === 'student') {
                $gradeId = $user->student?->section?->grade_id;
                if ($gradeId) {
                    $allowedRoles->push($this->targetRoleForGrade($gradeId));
                }
            }

            if ($user->role === 'parent') {
                $gradeIds = $user->parentInfo
                    ?->students()->with('section')->get()
                    ->pluck('section.grade_id')->filter()->unique() ?? collect();

                foreach ($gradeIds as $gradeId) {
                    $allowedRoles->push($this->targetRoleForGrade($gradeId));
                }
            }

            $allowedRoles = $allowedRoles->filter()->unique();

            $ownAdIds = $user->role === 'teacher'
                ? Announcement::where('user_id', $user->id)->pluck('id')
                : collect();

            $ads = $ads->filter(fn($ad) =>
                $allowedRoles->contains($ad->target_role) || $ownAdIds->contains($ad->id)
            );
        }

        $grouped = $ads->groupBy('target_role')->map(fn($group) => $group->map(fn($ad) => [
            'ad_id'       => $ad->id,
            'title'       => $ad->title,
            'content'     => $ad->content,
            'target_role' => $ad->target_role,
            'type'        => $ad->type,
            'created_at'  => $ad->created_at,
            'is_mine'     => $ad->user_id === $user->id,
        ]));

        return response()->json(['ads' => $grouped]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'teacher'])) {
            return response()->json(['message' => 'غير مصرح لك بإنشاء إعلان'], 403);
        }

        $request->validate([
            'title'       => 'required|string',
            'content'     => 'required|string',
            'target_role' => 'required|in:all,teachers,first_grade,second_grade,third_grade',
            'image'       => 'nullable|image|max:2048',
            'type'        => 'required|in:normal,urgent,important,activity,rule_policy,administrative,academic',
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

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'admin' && $announcement->user_id !== $user->id) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هاد الإعلان'], 403);
        }

        $request->validate([
            'title'       => 'sometimes|required|string',
            'content'     => 'sometimes|required|string',
            'target_role' => 'sometimes|required|in:all,teachers,first_grade,second_grade,third_grade',
            'image'       => 'nullable|image|max:2048',
            'type'        => 'sometimes|required|in:normal,urgent,important,activity,rule_policy,administrative,academic',
        ]);

        if ($request->hasFile('image')) {
            $announcement->image = $request->file('image')->store('ads', 'public');
        }

        $announcement->fill($request->only(['title', 'content', 'target_role', 'type']));
        $announcement->save();

        return response()->json([
            'message' => 'تم تعديل الإعلان بنجاح',
            'ad'      => $announcement,
        ]);
    }

    private function gradeIdForOrdinal(int $index)
    {
        return Grade::orderBy('id')->skip($index)->value('id');
    }

    private function targetRoleForGrade(?int $gradeId): ?string
    {
        if (!$gradeId) {
            return null;
        }

        $map = ['first_grade' => 0, 'second_grade' => 1, 'third_grade' => 2];

        foreach ($map as $role => $index) {
            if ($this->gradeIdForOrdinal($index) === $gradeId) {
                return $role;
            }
        }

        return null;
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

    $gradeId = $this->gradeIdForOrdinal($gradeIndex);

    if (!$gradeId) {
        return collect();
    }

    return \App\Models\Student::whereHas('section', function ($q) use ($gradeId) {
        $q->where('grade_id', $gradeId);
    })->with('user')->get()->pluck('user_id');
}
public function destroy($id)
{
    $announcement = Announcement::findOrFail($id);
    $user = auth()->user();

    if ($user->role !== 'admin' && $announcement->user_id !== $user->id) {
        return response()->json(['message' => 'غير مصرح لك بحذف هاد الإعلان'], 403);
    }

    $announcement->delete();

    return response()->json(['message' => 'تم حذف الإعلان بنجاح']);
}
}
