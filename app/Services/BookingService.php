<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingService
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly BookingRepository $bookingRepository,
    ) {}

    public function createBooking(
        int $userId,
        int $roomId,
        string $title,
        string $start,
        string $end,
    ): Booking {
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

        if ($endTime->lessThanOrEqualTo($startTime) || $startTime->lessThan(Carbon::now('UTC'))) {
            throw new ApiException(
                'INVALID_TIME',
                'Waktu tidak valid: end_time harus setelah start_time dan start_time tidak boleh di masa lampau.',
                400
            );
        }

        return DB::transaction(function () use ($userId, $roomId, $title, $startTime, $endTime) {
            $room = $this->roomRepository->findByIdForUpdate($roomId);

            if (! $room) {
                throw new ApiException(
                    'ROOM_NOT_FOUND',
                    'Ruangan tidak ditemukan.',
                    404
                );
            }

            if (! $room->is_active) {
                throw new ApiException(
                    'ROOM_INACTIVE',
                    'Ruangan sedang tidak aktif dan tidak dapat dipesan.',
                    400
                );
            }

            if ($this->bookingRepository->hasOverlap($roomId, $startTime, $endTime)) {
                throw new ApiException(
                    'ROOM_CONFLICT',
                    'Ruangan sudah dipesan pada rentang waktu tersebut.',
                    409
                );
            }

            return $this->bookingRepository->create([
                'room_id' => $roomId,
                'user_id' => $userId,
                'title' => $title,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'CONFIRMED',
            ]);
        });
    }

    public function cancelBooking(int $bookingId, int $userId, string $userRole): Booking
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (! $booking) {
            throw new ApiException(
                'BOOKING_NOT_FOUND',
                'Data pemesanan tidak ditemukan.',
                404
            );
        }

        if ($booking->user_id !== $userId && $userRole !== 'ADMIN') {
            throw new ApiException(
                'FORBIDDEN',
                'Anda tidak memiliki izin untuk membatalkan pesanan ini.',
                403
            );
        }

        return $this->bookingRepository->cancel($booking);
    }
}