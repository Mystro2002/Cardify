<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
        /**
     * success response method.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($data, $message, $status = true)
    {
        $response = [
            'status' => $status,
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($response);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($data, $message, $status = false, $httpCode = 500)
    {
        $response = [
            'status' => $status,
            'data' => $data,
            'message' => $message
        ];

        return response()->json($response, $httpCode);
    }
}
