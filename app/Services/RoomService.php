<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Room;
use App\Repositories\RoomRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class RoomService
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
    ) {}

    public function createRoom(string $name, int $capacity): Room
    {
        return $this->roomRepository->create([
            'name' => $name,
            'capacity' => $capacity,
            'is_active' => true,
        ]);
    }

    public function getAvailableRooms(?string $start, ?string $end): Collection
    {
        if (! $start || ! $end) {
            throw new ApiException(
                'INVALID_TIME',
                'Query parameter start dan end wajib diisi dalam format ISO-8601 UTC.',
                400
            );
        }

        try {
            $startTime = Carbon::parse($start)->utc();
            $endTime = Carbon::parse($end)->utc();
        } catch (Throwable) {
            throw new ApiException(
                'INVALID_TIME',
                'Format waktu tidak valid. Gunakan format ISO-8601 UTC.',
                400
            );
        }

        if ($endTime->lessThanOrEqualTo($startTime)) {
            throw new ApiException(
                'INVALID_TIME',
                'Waktu selesai harus lebih besar dari waktu mulai.',
                400
            );
        }

        return $this->roomRepository->findAvailable($startTime, $endTime);
    }
}