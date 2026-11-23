<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
        ]);

        $userId = (int) $request->attributes->get('auth_user_id');

        $booking = $this->bookingService->createBooking(
            $userId,
            (int) $validated['room_id'],
            $validated['title'],
            $validated['start_time'],
            $validated['end_time'],
        );

        return response()->json([
            'id' => $booking->id,
            'room_id' => $booking->room_id,
            'user_id' => $booking->user_id,
            'title' => $booking->title,
            'start_time' => $booking->start_time->utc()->toIso8601ZuluString(),
            'end_time' => $booking->end_time->utc()->toIso8601ZuluString(),
            'status' => $booking->status,
        ], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id');
        $userRole = (string) $request->attributes->get('auth_user_role');

        $booking = $this->bookingService->cancelBooking($id, $userId, $userRole);

        return response()->json([
            'id' => $booking->id,
            'status' => $booking->status,
        ], 200);
    }
}