<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = rtrim(env('TEST_BASE_URL', 'http://nginx'), '/');
    }

    private function login(string $email, string $password = 'password123'): string
    {
        $response = Http::post("{$this->baseUrl}/api/v1/auth/login", [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertEquals(200, $response->status(), 'Login gagal: '.$response->body());

        return $response->json('token');
    }

    private function createRoom(string $adminToken, string $name): int
    {
        $response = Http::withToken($adminToken)->post("{$this->baseUrl}/api/v1/rooms", [
            'name' => $name,
            'capacity' => 10,
        ]);

        $this->assertEquals(201, $response->status(), 'Gagal membuat ruangan: '.$response->body());

        return (int) $response->json('id');
    }

    public function test_overlapping_booking_returns_409_and_can_rebook_after_cancel(): void
    {
        $adminToken = $this->login('admin@test.com');
        $userToken = $this->login('user1@test.com');
        $roomId = $this->createRoom($adminToken, 'Ruang Overlap Test '.uniqid());

        $start = Carbon::now('UTC')->addDays(10)->setTime(10, 0, 0)->toIso8601ZuluString();
        $end = Carbon::now('UTC')->addDays(10)->setTime(12, 0, 0)->toIso8601ZuluString();

        $first = Http::withToken($userToken)->post("{$this->baseUrl}/api/v1/bookings", [
            'room_id' => $roomId,
            'title' => 'Sprint Planning',
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $this->assertEquals(201, $first->status());
        $bookingId = $first->json('id');

        $overlapStart = Carbon::now('UTC')->addDays(10)->setTime(11, 0, 0)->toIso8601ZuluString();
        $overlapEnd = Carbon::now('UTC')->addDays(10)->setTime(13, 0, 0)->toIso8601ZuluString();

        $conflict = Http::withToken($userToken)->post("{$this->baseUrl}/api/v1/bookings", [
            'room_id' => $roomId,
            'title' => 'Rapat Bentrok',
            'start_time' => $overlapStart,
            'end_time' => $overlapEnd,
        ]);

        $this->assertEquals(409, $conflict->status());
        $this->assertEquals('ROOM_CONFLICT', $conflict->json('error_code'));

        $cancel = Http::withToken($userToken)->patch("{$this->baseUrl}/api/v1/bookings/{$bookingId}/cancel");

        $this->assertEquals(200, $cancel->status());
        $this->assertEquals('CANCELLED', $cancel->json('status'));

        $rebook = Http::withToken($userToken)->post("{$this->baseUrl}/api/v1/bookings", [
            'room_id' => $roomId,
            'title' => 'Rapat Pengganti',
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $this->assertEquals(201, $rebook->status());
    }

    public function test_ten_concurrent_booking_requests_only_allow_one_success(): void
    {
        $adminToken = $this->login('admin@test.com');
        $user1Token = $this->login('user1@test.com');
        $user2Token = $this->login('user2@test.com');
        $roomId = $this->createRoom($adminToken, 'Ruang Concurrency Test '.uniqid());

        $start = Carbon::now('UTC')->addDays(15)->setTime(14, 0, 0)->toIso8601ZuluString();
        $end = Carbon::now('UTC')->addDays(15)->setTime(16, 0, 0)->toIso8601ZuluString();

        $responses = Http::pool(function (Pool $pool) use ($user1Token, $user2Token, $roomId, $start, $end) {
            $requests = [];

            for ($i = 1; $i <= 10; $i++) {
                $token = $i % 2 === 0 ? $user2Token : $user1Token;

                $requests[] = $pool
                    ->withToken($token)
                    ->acceptJson()
                    ->post("{$this->baseUrl}/api/v1/bookings", [
                        'room_id' => $roomId,
                        'title' => "Concurrent Booking #{$i}",
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
            }

            return $requests;
        });

        $createdCount = 0;
        $conflictCount = 0;

        foreach ($responses as $response) {
            if ($response->status() === 201) {
                $createdCount++;
            } elseif ($response->status() === 409) {
                $this->assertEquals('ROOM_CONFLICT', $response->json('error_code'));
                $conflictCount++;
            } else {
                $this->fail("Status code tidak terduga: {$response->status()} - {$response->body()}");
            }
        }

        $this->assertEquals(1, $createdCount, 'Harus tepat 1 request yang berhasil (201 Created).');
        $this->assertEquals(9, $conflictCount, 'Harus tepat 9 request yang ditolak (409 Conflict).');
    }
}