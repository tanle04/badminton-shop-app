<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * API Key Authentication Middleware
 * 
 * Dùng để bảo vệ các API Bridge endpoints
 * BackendApi phải gửi X-API-Key header
 */
class ApiKeyAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        // Lấy API key từ config hoặc .env
        $validApiKey = config('services.bridge_api.key');

        // Nếu không set API key trong config, dùng fallback
        if (empty($validApiKey)) {
            $validApiKey = env('BRIDGE_API_KEY', 'your-secret-api-key-here');
        }

        // Kiểm tra API key
        if (empty($apiKey) || $apiKey !== $validApiKey) {
            \Log::warning('🚫 Invalid API Key attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Invalid API Key'
            ], 401);
        }

        \Log::info('✅ API Key authenticated', [
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
        ]);

        return $next($request);
    }
}