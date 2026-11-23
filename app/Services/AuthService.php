<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function login(string $email, string $password): string
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! password_verify($password, $user->password_hash)) {
            throw new ApiException(
                'INVALID_CREDENTIALS',
                'Email atau password salah.',
                401
            );
        }

        $now = time();
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + config('jwt.ttl'),
        ];

        return JWT::encode($payload, config('jwt.secret'), 'HS256');
    }

    public function decodeToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
        } catch (Throwable) {
            throw new ApiException(
                'INVALID_TOKEN',
                'Token tidak valid atau sudah kedaluwarsa.',
                401
            );
        }
    }
}