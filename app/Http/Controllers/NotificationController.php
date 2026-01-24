<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Traits\HandlesApiErrors;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use HandlesApiErrors;

    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $notifications = $user->notifications()
                ->when($request->has('unread_only'), function ($query) {
                    return $query->whereNull('read_at');
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ApiResponse::success($notifications);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching notifications');
        }
    }

    public function unreadCount(Request $request)
    {
        try {
            $count = $request->user()->unreadNotifications()->count();

            return ApiResponse::success(['unread_count' => $count]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching unread count');
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->where('id', $id)
                ->firstOrFail();

            $notification->markAsRead();

            return ApiResponse::success(null, 'Notification marked as read');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Notification not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Marking notification as read');
        }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications->markAsRead();

            return ApiResponse::success(null, 'All notifications marked as read');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Marking all notifications as read');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $notification = $request->user()
                ->notifications()
                ->where('id', $id)
                ->firstOrFail();

            $notification->delete();

            return ApiResponse::success(null, 'Notification deleted');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Notification not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Notification deletion');
        }
    }
}
