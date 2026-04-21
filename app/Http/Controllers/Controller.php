<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function success($message, $data = null, $code = 200) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error($message, $data = null, $code = 400) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
