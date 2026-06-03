@extends('user.layout')

@section('title', 'My Numbers')
@section('header', 'Active Virtual Numbers')

@section('content')
    <style>
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
        .fetch-btn {
            background: rgba(59, 130, 246, 0.1) !important;
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
            color: var(--accent) !important;
            padding: 0.45rem 1rem !important;
            font-size: 0.85rem !important;
            border-radius: 6px !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .fetch-btn:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
        }
        .fetch-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i class="fa-solid fa-check"></i> Active</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">Closed</span>
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                {{ $num->created_at->diffForHumans() }}
                            </td>
                            <td style="min-width: 320px; vertical-align: top;">
                                <div class="sms-container" style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                    @if($num->status === 'active')
                                        <button class="btn fetch-btn" onclick="fetchOtpManual('{{ $num->id }}', this)">
                                            <i class="fa-solid fa-satellite-dish"></i> Fetch OTP
                                        </button>
                                    @else
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;">SMS Checked (Closed)</span>
                                    @endif
                                    <div class="sms-result-display" data-number-id="{{ $num->id }}"></div>
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
    function fetchOtpManual(id, button) {
        const display = document.querySelector(`.sms-result-display[data-number-id="${id}"]`);
        if (!display) return;

        // Disable button and show loading state to prevent double clicking and wasting API quota
        button.disabled = true;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Fetching...';
        display.innerHTML = '<span style="color:var(--text-secondary); font-size:0.85rem;"><i class="fa-solid fa-spinner fa-spin"></i> Connecting to SMS Gateway... (Consuming 1 API call)</span>';

        fetch(`/app/sms/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.has_sms) {
                        // Successfully received SMS!
                        // Hide button completely to save further API calls
                        button.style.display = 'none';
                        
                        let otpBadge = '';
                        if (data.otp) {
                            otpBadge = `
                                <div class="otp-badge-premium" onclick="copyTextValue('${data.otp}', this)" title="Click to copy OTP">
                                    <span class="otp-number">${data.otp}</span>
                                    <i class="fa-solid fa-copy copy-badge-icon"></i>
                                </div>
                            `;
                        }

                        display.innerHTML = `
                            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 5px; margin-top: 5px;">
                                ${otpBadge}
                                <div class="sms-text-bubble">
                                    ${data.sms}
                                </div>
                                <div class="sms-meta-info">
                                    Sender: ${data.from} • ${data.time}
                                </div>
                            </div>
                        `;
                    } else {
                        // No SMS yet
                        display.innerHTML = `<span style="color:var(--warning); font-size:0.85rem;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.message}</span>`;
                        
                        // Re-enable button after 5 seconds to let them retry
                        setTimeout(() => {
                            button.disabled = false;
                            button.innerHTML = originalHTML;
                        }, 4000);
                    }
                } else {
                    // API connection failure or error
                    display.innerHTML = `<span style="color:var(--danger); font-size:0.85rem;"><i class="fa-solid fa-circle-xmark"></i> ${data.message}</span>`;
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    }, 4000);
                }
            })
            .catch(err => {
                display.innerHTML = '<span style="color:var(--danger); font-size:0.85rem;"><i class="fa-solid fa-circle-xmark"></i> Connection Error. Please retry.</span>';
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                }, 4000);
            });
    }

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
