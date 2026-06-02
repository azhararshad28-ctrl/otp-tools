<?php

namespace App\Jobs;

use App\Models\OtpLog;
use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExtractOtpJob implements ShouldQueue
{
    use Queueable;

    protected int $smsLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $smsLogId)
    {
        $this->smsLogId = $smsLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $smsLog = SmsLog::find($this->smsLogId);

            if (!$smsLog) {
                return;
            }

            // Get custom regex pattern from settings, or use default for 4-8 digits
            $pattern = Setting::where('key', 'otp_regex_pattern')->value('value') ?? '/\b\d{4,8}\b/';

            if (preg_match($pattern, $smsLog->message, $matches)) {
                $otpCode = $matches[0];
                
                // Save to sms log for quick display
                $smsLog->update(['otp' => $otpCode]);

                // Avoid duplicate OTP logging for same SMS
                $existingOtp = OtpLog::where('sms_log_id', $smsLog->id)->first();
                
                if (!$existingOtp) {
                    OtpLog::create([
                        'sms_log_id' => $smsLog->id,
                        'code' => $otpCode
                    ]);
                    
                    Log::info("ExtractOtpJob: OTP extracted ({$otpCode}) from SMS ID {$smsLog->id}");
                }
            }
        } catch (\Exception $e) {
            Log::error("ExtractOtpJob Exception for SMS ID {$this->smsLogId}: " . $e->getMessage());
        }
    }
}
