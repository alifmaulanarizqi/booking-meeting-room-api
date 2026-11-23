<?php

namespace App\Repositories;

use App\Models\Booking;
use Carbon\CarbonInterface;

class BookingRepository
{
    public function hasOverlap(int $roomId, CarbonInterface $start, CarbonInterface $end): bool
    {
        return Booking::where('room_id', $roomId)
            ->where('status', 'CONFIRMED')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function findById(int $id): ?Booking
    {
        return Booking::find($id);
    }

    public function cancel(Booking $booking): Booking
    {
        $booking->status = 'CANCELLED';
        $booking->save();

        return $booking;
    }
}