<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($n) => [
                'notification_id' => $n->id,
                'title'           => $n->title,
                'message'         => $n->message,
                'type'            => $n->type,
                'is_read'         => $n->is_read,
                'created_at'      => $n->created_at,
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $notifications->where('is_read', false)->count(),
        ]);
    }

    
    public function markAsRead($notification_id)
    {
        $notification = Notification::where('id', $notification_id)
            ->where('user_id', auth()->id())->firstOrFail();
        $notification->update(['is_read' => true]);
        return response()->json(['message' => 'تم تعليم الإشعار كمقروء']);
    }
}
