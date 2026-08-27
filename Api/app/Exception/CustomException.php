<?php

namespace App\Exception;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomException extends Exception
{
    public static function exception($error = null, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): CustomException
    {
        if ($error instanceof HttpResponseException) {
            throw new HttpResponseException(
                response()->json(['error' => $error])
            );
        }
    
        if (is_array($error) || is_string($error)) {
            Log::info("Exceção lançada: " . json_encode($error));
            throw new HttpResponseException(
                response()->json(['error' => $error], $status)
            );
        }
    
        if ($error instanceof QueryException) {
            Log::info("Exceção lançada: " . json_encode($error));
            throw new HttpResponseException(
                response()->json(['error' => $error->getMessage()], $status)
            );
        }
    
        if ($error instanceof Exception) {
            Log::info("Exceção lançada: " . json_encode($error));
            throw new HttpResponseException(
                response()->json(['error' => $error->getMessage()], $error->getCode() ?: $status)
            );
        }

        throw new HttpResponseException(
            response()->json(
                ['error' => Lang::get("Messages Errors Unexpected Error")],
                Response::HTTP_INTERNAL_SERVER_ERROR
            )
        );
    }
}