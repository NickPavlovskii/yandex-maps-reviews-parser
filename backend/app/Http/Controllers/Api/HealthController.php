<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $redis = 'ok';

        try {
            Redis::connection()->ping();
        } catch (\Throwable) {
            $redis = 'unavailable';
        }

        return response()->json([
            'status' => 'ok',
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'redis' => $redis,
        ]);
    }
}
