<?php

namespace App\Services\User;

use App\Repositories\User\UserRpository;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class SignInService {
    
    private UserRpository $userRepository;

    public function __construct(UserRpository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(array $input)
    {
        $user = $this->userRepository->getUserByEmail(
            $input['email']
        );


        if (!Hash::check($input['password'], $user->password)) {
            throw new Exception("Usuario ou senha inválidos.", Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken($user->email)->plainTextToken; 

        return (object) [
            'name'  => $user->name,
            'token' => $token
        ];
    }
}