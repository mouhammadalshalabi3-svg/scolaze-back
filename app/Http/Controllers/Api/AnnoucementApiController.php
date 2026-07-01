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
            'target_role' => 'required|string',
            'image'       => 'nullable|image|max:2048',
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
]);
        $userIds = $request->target_role === 'all'
    ? User::pluck('id')
    : User::where('role', $request->target_role)->pluck('id');

         NotificationService::sendToMany($userIds, $ad->title, $ad->content, 'announcement', $imagePath);
        return response()->json([
            'message' => 'تم إنشاء الإعلان بنجاح',
            'ad'      => $ad,
        ], 201);
    }
}
