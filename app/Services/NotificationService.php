<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    public static function send(int $userId, string $title, string $message, string $type, ?string $image = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'image'   => $image,
            'is_read' => false,
        ]);
    }

    public static function sendToMany(Collection|array $userIds, string $title, string $message, string $type, ?string $image = null): void
    {
        $now = now();
        $rows = collect($userIds)->unique()->filter()->map(fn($id) => [
            'user_id'    => $id,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'image'      => $image,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($rows)) {
            Notification::insert($rows);
        }
    }
}