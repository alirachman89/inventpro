<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $items = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Notification $n) => $this->transform($n));

        return Inertia::render('Notifications/Index', [
            'notifications' => $items,
            'filter' => $filter ?: 'all',
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $this->notifications->unreadCount($user),
            'items' => $this->notifications->latestForUser($user)->map(fn (Notification $n) => $this->transform($n)),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse|JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function transform(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'read_at' => $notification->read_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'is_unread' => $notification->read_at === null,
            'created_at' => $notification->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }
}
