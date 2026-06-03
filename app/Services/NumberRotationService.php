<?php

namespace App\Services;

use App\Models\PhoneNumber;
use App\Models\SystemAuditLog;
use App\Models\Setting;
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

        $cooldownHours = (int)(Setting::where('key', 'cooldown_hours')->value('value') ?? 24);
        $busyLockMinutes = (int)(Setting::where('key', 'busy_lock_minutes')->value('value') ?? 15);

        $scoredNumbers = [];

        foreach ($apiNumbers as $numValue) {
            // 1. Check if the number is currently busy (active session by ANY user for ANY service within busy lock window)
            $isBusy = PhoneNumber::where('number', 'like', "%{$numValue}")
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subMinutes($busyLockMinutes))
                ->exists();

            if ($isBusy) {
                self::logEvent('NUMBER_FILTERED', "Number +{$numValue} filtered out: Currently active (busy lock).");
                continue;
            }

            // 2. Check if the number is under cooldown for this specific service
            // (discarded or closed within the cooldown window)
            $underCooldown = PhoneNumber::where('number', 'like', "%{$numValue}")
                ->where('service', $serviceName)
                ->where(function($query) {
                    $query->where('status', 'discarded')
                          ->orWhere('status', 'closed');
                })
                ->where('updated_at', '>=', now()->subHours($cooldownHours))
                ->exists();

            if ($underCooldown) {
                self::logEvent('NUMBER_FILTERED', "Number +{$numValue} filtered out: In cooldown period ({$cooldownHours}h) for " . ucfirst($serviceName));
                continue;
            }

            // 3. Check reputation from the latest record of this number for this service
            $dbNumber = PhoneNumber::where('number', 'like', "%{$numValue}")
                ->where('service', $serviceName)
                ->orderBy('id', 'desc')
                ->first();

            if ($dbNumber) {
                // Skip if reputation score is too low
                if ($dbNumber->reputation_score < 40) {
                    self::logEvent('NUMBER_FILTERED', "Number +{$numValue} filtered out: Low reputation score ({$dbNumber->reputation_score}) for " . ucfirst($serviceName));
                    continue;
                }

                $scoredNumbers[] = [
                    'number' => $numValue,
                    'reputation' => $dbNumber->reputation_score,
                    'last_used' => $dbNumber->last_used_at ? strtotime($dbNumber->last_used_at) : 0
                ];
            } else {
                // Fresh numbers start with a clean slate
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
