<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        $room = $this->roomService->createRoom(
            $validated['name'],
            (int) $validated['capacity'],
        );

        return response()->json([
            'id' => $room->id,
            'name' => $room->name,
            'capacity' => $room->capacity,
            'is_active' => $room->is_active,
        ], 201);
    }

    public function available(Request $request): JsonResponse
    {
        $rooms = $this->roomService->getAvailableRooms(
            $request->query('start'),
            $request->query('end'),
        );

        return response()->json([
            'data' => $rooms->map(fn ($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'capacity' => $room->capacity,
            ])->values(),
        ], 200);
    }
}