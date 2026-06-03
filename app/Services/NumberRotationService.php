<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Models\SystemAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class NumberRotationService
{
    /**
     * Log a system audit event.
     */
    public static function logEvent(string $action, ?string $description = null): void
    {
        try {
            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if DB logging fails to avoid blocking the main user flow
        }
    }

    /**
     * Record a successful OTP verification.
     */
    public function recordSuccess(int $numberId): void
    {
        $number = PhoneNumber::find($numberId);
        if (!$number) return;

        $number->success_count += 1;
        $number->verification_count += 1;
        $number->last_used_at = now();
        $number->reputation_score = $this->calculateReputation($number);
        $number->save();

        self::logEvent('OTP_SUCCESS', "Successfully received OTP on +{$number->number} for " . ucfirst($number->service));
    }

    /**
     * Record a failed verification attempt.
     */
    public function recordFailure(int $numberId): void
    {
        $number = PhoneNumber::find($numberId);
        if (!$number) return;

        $number->fail_count += 1;
        $number->verification_count += 1;
        $number->reputation_score = $this->calculateReputation($number);
        $number->save();

        self::logEvent('OTP_FAILURE', "Failed/Timed out waiting for OTP on +{$number->number} for " . ucfirst($number->service));
    }

    /**
     * Record an active user discard (e.g. number already registered on the target platform).
     */
    public function recordDiscard(int $numberId): void
    {
        $number = PhoneNumber::find($numberId);
        if (!$number) return;

        $number->fail_count += 1;
        $number->verification_count += 1;
        
        // Discarding due to registration blockage is a severe penalty (reduces score by 25)
        $number->reputation_score = max(0, $this->calculateReputation($number) - 25);

        // If reputation score drops below 40, mark number as inactive (prune)
        if ($number->reputation_score < 40) {
            $number->status = 'closed';
            self::logEvent('NUMBER_PRUNED', "Number +{$number->number} automatically deactivated due to poor reputation ({$number->reputation_score})");
        } else {
            self::logEvent('NUMBER_DISCARDED', "User discarded number +{$number->number} (Reputation: {$number->reputation_score})");
        }

        $number->save();
    }

    /**
     * Sort and filter a list of API numbers based on database metrics.
     */
    public function selectBestNumber(array $apiNumbers, int $countryId, string $serviceName): ?string
    {
        if (empty($apiNumbers)) return null;

        $scoredNumbers = [];

        foreach ($apiNumbers as $numValue) {
            // Find existing number in database
            $dbNumber = PhoneNumber::where('number', 'like', "%{$numValue}")
                ->where('service', $serviceName)
                ->first();

            if ($dbNumber) {
                // If the number is marked inactive, skip it
                if ($dbNumber->status !== 'active') {
                    continue;
                }
                
                // Skip if reputation score is too low
                if ($dbNumber->reputation_score < 40) {
                    continue;
                }

                $scoredNumbers[] = [
                    'number' => $numValue,
                    'reputation' => $dbNumber->reputation_score,
                    'last_used' => $dbNumber->last_used_at ? strtotime($dbNumber->last_used_at) : 0
                ];
            } else {
                // Fresh numbers start with a clean slate (score 100, last used never/0)
                $scoredNumbers[] = [
                    'number' => $numValue,
                    'reputation' => 100,
                    'last_used' => 0
                ];
            }
        }

        if (empty($scoredNumbers)) return null;

        // Sort: 1. Reputation DESC (higher is better)
        //       2. Last Used ASC (older usage is better to ensure rotation)
        usort($scoredNumbers, function ($a, $b) {
            if ($a['reputation'] !== $b['reputation']) {
                return $b['reputation'] <=> $a['reputation'];
            }
            return $a['last_used'] <=> $b['last_used'];
        });

        return $scoredNumbers[0]['number'];
    }

    /**
     * Helper to compute reputation rating from success/failure stats.
     */
    protected function calculateReputation($number): int
    {
        $total = $number->verification_count;
        if ($total === 0) return 100;

        $successRate = ($number->success_count / $total) * 100;

        // Apply a small penalty for each failure
        $penalty = $number->fail_count * 5;

        $score = round($successRate - $penalty);

        // Clamp between 0 and 100
        return max(0, min(100, (int)$score));
    }
}
