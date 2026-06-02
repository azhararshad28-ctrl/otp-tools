<?php

namespace App\Jobs;

use App\Models\PhoneNumber;
use App\Models\SmsLog;
use App\Services\ProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSmsJob implements ShouldQueue
{
    use Queueable;

    protected int $phoneNumberId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $phoneNumberId)
    {
        $this->phoneNumberId = $phoneNumberId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProviderInterface $provider): void
    {
        try {
            $phoneNumber = PhoneNumber::with('country')->find($this->phoneNumberId);

            if (!$phoneNumber || !$phoneNumber->country) {
                return;
            }

            $messages = $provider->checkSmsHistory($phoneNumber->country->code, $phoneNumber->number);
            
            $phoneNumber->update(['last_checked' => now()]);

            if (empty($messages)) {
                return;
            }

            foreach ($messages as $msg) {
                // Assuming msg structure: sender, message, received_time (timestamp or string)
                if (isset($msg['message']) && isset($msg['sender'])) {
                    
                    // We only create if this message is new. In a real system, you need a message ID or unique hash.
                    $existing = SmsLog::where('phone_number_id', $phoneNumber->id)
                        ->where('message', $msg['message'])
                        ->where('received_time', date('Y-m-d H:i:s', strtotime($msg['received_time'] ?? now())))
                        ->first();

                    if (!$existing) {
                        $smsLog = SmsLog::create([
                            'phone_number_id' => $phoneNumber->id,
                            'sender' => $msg['sender'],
                            'message' => $msg['message'],
                            'received_time' => date('Y-m-d H:i:s', strtotime($msg['received_time'] ?? now())),
                        ]);

                        // Dispatch extraction job for new SMS
                        ExtractOtpJob::dispatch($smsLog->id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("CheckSmsJob Exception for ID {$this->phoneNumberId}: " . $e->getMessage());
        }
    }
}
