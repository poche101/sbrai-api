<?php

namespace App\Services;

use App\Models\NINVerificationLog;
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
     */
    public function verifyNIN(string $nin): array
    {
        try {
            // Check if NIN was recently verified (e.g., in the last 24 hours)
            $existingVerification = NINVerificationLog::where('nin', $nin)
                ->where('verification_status', 'verified')
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existingVerification) {
                return [
                    'status' => 'success',
                    'message' => 'NIN already verified',
                    'data' => $existingVerification->response_data,
                    'from_cache' => true,
                ];
            }

            // Create log entry
            $log = NINVerificationLog::create([
                'nin' => $nin,
                'status' => 'pending',
                'verification_status' => 'pending',
                'request_data' => ['nin' => $nin],
            ]);

            // Make API request to Mono
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'mono-sec-key' => $this->secretKey,
            ])->post($this->baseUrl.'/lookup/nin', [
                'nin' => $nin,
            ]);

            // Log the response
            $log->update([
                'response_data' => $response->json(),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if the response has the expected structure
                if (isset($data['status']) && $data['status'] === 'successful') {
                    $ninData = $data['data'];

                    // Update log with detailed information
                    $log->update([
                        'verification_status' => 'verified',
                        'tracking_id' => $ninData['tracking_id'] ?? null,
                        'central_id' => $ninData['central_iD'] ?? null,
                        'firstname' => $ninData['firstname'] ?? null,
                        'middlename' => $ninData['middlename'] ?? null,
                        'surname' => $ninData['surname'] ?? null,
                        'photo' => $ninData['photo'] ?? null,
                        'signature' => $ninData['signature'] ?? null,
                        'birthdate' => $ninData['birthdate'] ?? null,
                        'gender' => $ninData['gender'] ?? null,
                        'phone_number' => $ninData['telephoneno'] ?? null,
                        'email' => $ninData['email'] ?? null,
                        'profession' => $ninData['profession'] ?? null,
                        'residence_address' => $ninData['residence_address'] ?? null,
                    ]);

                    return [
                        'status' => 'success',
                        'message' => $data['message'] ?? 'NIN verified successfully',
                        'data' => $ninData,
                        'tracking_id' => $ninData['tracking_id'] ?? null,
                    ];
                }
            }

            // If verification failed
            $log->update(['verification_status' => 'failed']);

            return [
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'NIN verification failed',
                'error' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('Mono NIN Verification Error: '.$e->getMessage());

            // Update log if it exists
            if (isset($log)) {
                $log->update([
                    'verification_status' => 'failed',
                    'response_data' => ['error' => $e->getMessage()],
                ]);
            }

            return [
                'status' => 'error',
                'message' => 'NIN verification service error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get verification history for a NIN
     */
    public function getVerificationHistory(string $nin)
    {
        return NINVerificationLog::where('nin', $nin)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
