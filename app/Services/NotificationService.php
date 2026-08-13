<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * @param  iterable<int, User|string>  $users
     */
    public function sendToUsers(
        iterable $users,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
    ): void {
        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;

            Notification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'data' => $data,
                'created_at' => now(),
            ]);
        }
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function latestForUser(User $user, int $limit = 8): Collection
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
