<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoNINService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.withmono.com/v3';
        $this->secretKey = config('services.mono.secret_key');
    }

    /**
     * Verify NIN using Mono API
     * No personal data is stored in database for privacy compliance
     */
    public function verifyNIN(string $nin): array
    {
        try {
            // Make API request to Mono
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'mono-sec-key' => $this->secretKey,
            ])->post($this->baseUrl . '/lookup/nin', [
                'nin' => $nin,
            ]);

            // Only log minimal metadata (no personal data)
            Log::info('NIN verification attempt', [
                'timestamp' => now()->toDateTimeString(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if the response has the expected structure
                if (isset($data['status']) && $data['status'] === 'successful') {
                    $ninData = $data['data'];

                    // Return data WITHOUT storing in database
                    return [
                        'status' => 'success',
                        'message' => $data['message'] ?? 'NIN verified successfully',
                        'data' => [
                            'firstname' => $ninData['firstname'] ?? null,
                            'middlename' => $ninData['middlename'] ?? null,
                            'surname' => $ninData['surname'] ?? null,
                            'photo' => $ninData['photo'] ?? null,
                            'signature' => $ninData['signature'] ?? null,
                            'birthdate' => $ninData['birthdate'] ?? null,
                            'gender' => $ninData['gender'] ?? null,
                            'telephoneno' => $ninData['telephoneno'] ?? null,
                            'email' => $ninData['email'] ?? null,
                            'profession' => $ninData['profession'] ?? null,
                            'residence_address' => $ninData['residence_address'] ?? null,
                            'tracking_id' => $ninData['tracking_id'] ?? null,
                            'central_iD' => $ninData['central_iD'] ?? null,
                        ],
                        'tracking_id' => $ninData['tracking_id'] ?? null,
                    ];
                }

                // Handle unexpected successful response structure
                Log::warning('Unexpected Mono API response structure', [
                    'response_keys' => array_keys($data),
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Unexpected response from verification service',
                    'error' => 'Invalid response structure',
                ];
            }

            // If API request failed
            Log::warning('NIN verification API request failed', [
                'http_status' => $response->status(),
            ]);

            return [
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'NIN verification failed',
                'error' => $response->json(),
            ];

        } catch (\Exception $e) {
            // Log error without NIN data
            Log::error('NIN Verification Service Error', [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'NIN verification service is temporarily unavailable. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }
}