<?php

namespace App\Http\Controllers;

use App\Exception\CustomException;
use App\Http\Requests\SignInUserRequest;
use App\Http\Requests\SignUpUserRequest;
use App\Http\Resources\User\SignInResource;
use App\Http\Resources\User\SignUpResource;
use App\Services\User\SignInService;
use App\Services\User\SignUpService;
use Illuminate\Support\Facades\DB;

class UserController extends Controller {

    public function signIn(SignInUserRequest $request, SignInService $service)
    {
        try {
            DB::beginTransaction();

            $response = $service->execute(
                $request->validated()
            );

            DB::commit();

            return (new SignInResource($response))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $error) {
            DB::rollBack();
            CustomException::exception($error);
        }
    }

    public function signUp(SignUpUserRequest $request, SignUpService $service)
    {
        try {
            DB::beginTransaction();

            $response = $service->execute(
                $request->validated()
            );
            
            DB::commit();

            return (new SignUpResource($response))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $error) {
            DB::rollBack();
            CustomException::exception($error);
        }
    }
}