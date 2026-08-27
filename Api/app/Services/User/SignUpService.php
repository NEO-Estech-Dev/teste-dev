<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\User\UserRpository;
use Illuminate\Support\Facades\Hash;

class SignUpService {

    private UserRpository $userRepository;

    public function __construct(UserRpository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(array $request): User
    {
        return $this->userRepository->create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password'])
        ]);
    }
}