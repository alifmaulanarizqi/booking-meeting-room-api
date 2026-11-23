<?php

namespace App\Repositories;

use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class RoomRepository
{
    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function findByIdForUpdate(int $id): ?Room
    {
        return Room::where('id', $id)->lockForUpdate()->first();
    }

    public function findAvailable(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Room::query()
            ->select(['id', 'name', 'capacity'])
            ->where('is_active', true)
            ->whereNotExists(function ($query) use ($start, $end) {
                $query->selectRaw('1')
                    ->from('bookings')
                    ->whereColumn('bookings.room_id', 'rooms.id')
                    ->where('bookings.status', 'CONFIRMED')
                    ->where('bookings.start_time', '<', $end)
                    ->where('bookings.end_time', '>', $start);
            })
            ->orderBy('id')
            ->get();
    }
}