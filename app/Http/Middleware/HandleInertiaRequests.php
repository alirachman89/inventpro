<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $notificationService = app(NotificationService::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                    'is_superadmin' => $user->isSuperAdmin(),
                ] : null,
            ],
            'notifications' => [
                'unread_count' => $user ? $notificationService->unreadCount($user) : 0,
                'latest' => $user
                    ? $notificationService->latestForUser($user)->map(fn ($n) => [
                        'id' => $n->id,
                        'title' => $n->title,
                        'message' => $n->message,
                        'link' => $n->link,
                        'is_unread' => $n->read_at === null,
                        'created_at' => $n->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                    ])->values()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
