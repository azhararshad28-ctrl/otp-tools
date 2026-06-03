@extends('user.layout')

@section('title', 'My Numbers')
@section('header', 'Active Virtual Numbers')

@section('content')
    <style>
        .sms-status-waiting {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--warning);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--warning);
            animation: blink-animation 1.5s infinite ease-in-out;
            display: inline-block;
        }
        .status-text {
            color: var(--warning);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .otp-badge-premium {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--success);
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.05);
        }
        .otp-badge-premium:hover {
            transform: translateY(-1px) scale(1.03);
            background: rgba(16, 185, 129, 0.22);
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);
        }
        .copy-badge-icon {
            font-size: 0.85rem;
            opacity: 0.7;
            letter-spacing: 0;
        }
        .sms-text-bubble {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.85rem;
            line-height: 1.4;
            max-width: 320px;
            word-break: break-word;
        }
        .sms-meta-info {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 3px;
        }
        @keyframes blink-animation {
            0% { opacity: 0.4; transform: scale(0.95); }
            50% { opacity: 1; transform: scale(1.1); }
            100% { opacity: 0.4; transform: scale(0.95); }
        }
    </style>

    <div class="panel-card glass">
        <div class="panel-header">
            <h2 class="panel-title">My Numbers Inventory</h2>
        </div>
        
        @if($numbers->count() === 0)
            <div style="text-align: center; padding: 4rem 1rem; color: var(--text-secondary);">
                <i class="fa-solid fa-sim-card" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>Your inventory is empty. Generate a virtual number to receive SMS.</p>
                <a href="{{ route('generate.page') }}" class="btn btn-primary mt-4">Generate Number</a>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Phone Number</th>
                            <th>Target Service</th>
                            <th>Region/Country</th>
                            <th>Status</th>
                            <th>Acquired At</th>
                            <th>Latest SMS / OTP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($numbers as $num)
                        <tr>
                            <td style="font-weight: 700; color: var(--accent); font-size: 1.15rem; letter-spacing: 1px;">
                                +{{ $num->number }}
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(139,92,246,0.1); color: #8b5cf6; text-transform: capitalize;">
                                    <i class="fa-brands fa-{{ $num->service == 'other' ? 'app-store' : $num->service }}"></i> {{ $num->service }}
                                </span>
                            </td>
                            <td>
                                <i class="fa-solid fa-location-dot" style="color:var(--text-secondary); margin-right:5px;"></i> {{ $num->country->name ?? 'Unknown' }}
                            </td>
                            <td>
                                @if($num->status === 'active')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i class="fa-solid fa-circle-notch fa-spin" style="margin-right: 5px;"></i> Active</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">Closed</span>
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                {{ $num->created_at->diffForHumans() }}
                            </td>
                            <td style="min-width: 320px;">
                                <div class="sms-poll-container" data-number-id="{{ $num->id }}">
                                    @if($num->status === 'active')
                                        <div class="sms-status-waiting">
                                            <span class="pulse-dot"></span>
                                            <span class="status-text">Waiting for SMS...</span>
                                        </div>
                                    @else
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;">Polling Stopped</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pollContainers = document.querySelectorAll('.sms-poll-container');
        const activeIds = Array.from(pollContainers)
            .filter(c => c.querySelector('.sms-status-waiting') !== null)
            .map(c => c.getAttribute('data-number-id'));

        if (activeIds.length === 0) return;

        function pollSms() {
            fetch("{{ route('sms.poll') }}")
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Object.entries(data.sms_data).forEach(([id, smsInfo]) => {
                            const container = document.querySelector(`.sms-poll-container[data-number-id="${id}"]`);
                            if (!container) return;

                            if (smsInfo.has_sms) {
                                let otpBadge = '';
                                if (smsInfo.otp) {
                                    otpBadge = `
                                        <div class="otp-badge-premium" onclick="copyTextValue('${smsInfo.otp}', this)" title="Click to copy OTP">
                                            <span class="otp-number">${smsInfo.otp}</span>
                                            <i class="fa-solid fa-copy copy-badge-icon"></i>
                                        </div>
                                    `;
                                }

                                container.innerHTML = `
                                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 5px;">
                                        ${otpBadge}
                                        <div class="sms-text-bubble">
                                            ${smsInfo.text}
                                        </div>
                                        <div class="sms-meta-info">
                                            Sender: ${smsInfo.from} • ${smsInfo.time}
                                        </div>
                                    </div>
                                `;
                            } else {
                                container.innerHTML = `
                                    <div class="sms-status-waiting">
                                        <span class="pulse-dot"></span>
                                        <span class="status-text">Waiting for SMS...</span>
                                    </div>
                                `;
                            }
                        });
                    }
                })
                .catch(err => console.error("Error polling SMS: ", err));
        }

        // Initial run
        pollSms();
        // Poll every 5 seconds
        setInterval(pollSms, 5000);
    });

    function copyTextValue(text, element) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = element.innerHTML;
            element.style.background = 'rgba(16, 185, 129, 0.25)';
            element.style.borderColor = 'var(--success)';
            element.innerHTML = '<span style="color:var(--success); font-size: 0.9rem; letter-spacing:0; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Copied!</span>';
            
            setTimeout(() => {
                element.style.background = '';
                element.style.borderColor = '';
                element.innerHTML = originalHTML;
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy code: ', err);
        });
    }
</script>
@endsection
