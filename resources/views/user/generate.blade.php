@extends('user.layout')

@section('title', 'Generate Number')
@section('header', 'Acquire New Number')

@section('content')
    <div class="panel-card glass" style="max-width: 600px; margin: 0 auto; padding: 2.5rem;">
        <h2 class="panel-title" style="margin-bottom: 1.5rem; font-size: 1.5rem;">Select Parameters</h2>
        <form action="{{ route('generate.number') }}" method="POST">
            @csrf
            


            <div class="form-group">
                <label class="form-label">
                    <i class="fa-brands fa-app-store-ios" style="color:var(--accent); margin-right:5px;"></i> Application / Service
                </label>
                <select name="service" class="form-control" required>
                    <option value="">-- Choose Service --</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="telegram">Telegram</option>
                    <option value="google">Google / Gmail</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="twitter">Twitter / X</option>
                    <option value="other">Other App</option>
                </select>
            </div>

            <div class="alert" style="background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.15); display: flex; gap: 15px; margin-top: 2rem;">
                <i class="fa-solid fa-circle-info" style="color: var(--accent); font-size: 1.5rem; margin-top: 3px;"></i>
                <div>
                    <h4 style="margin-bottom: 5px; font-weight: 600;">Cost Estimation</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">The cost of this number is dynamically calculated based on the selected country and service. The amount will be deducted from your wallet upon successful SMS reception.</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 10px;">
                <i class="fa-solid fa-satellite-dish"></i> Connect & Generate Number
            </button>
        </form>
    </div>

    <!-- Glassmorphic Success Modal for Generated Number -->
    @if(session('generated_number'))
        <div class="modal-overlay active" id="successModal">
            <div class="modal-content glass" style="max-width: 500px; text-align: center; padding: 2.5rem; position: relative;">
                <button class="close-btn" onclick="closeModal()" style="position: absolute; top: 1.5rem; right: 1.5rem; outline: none; border: none; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                
                <div class="success-icon-wrapper" style="margin-bottom: 1.5rem;">
                    <div style="width: 75px; height: 75px; background: rgba(16, 185, 129, 0.12); color: var(--success); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.75rem; border: 1px solid rgba(16, 185, 129, 0.25);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <h2 class="panel-title" style="font-size: 1.75rem; margin-bottom: 0.5rem; font-weight: 700;">Number Acquired!</h2>
                @if(session('generated_country_name'))
                    <p style="color: var(--accent); font-weight: 600; margin-bottom: 0.5rem; font-size: 1.1rem;">
                        <i class="fa-solid fa-flag"></i> Country: {{ session('generated_country_name') }}
                    </p>
                @endif
                <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem;">Your virtual number is ready to receive SMS logs.</p>

                <div class="number-box" style="background: rgba(15, 23, 42, 0.65); border: 1px solid var(--border-color); padding: 1.25rem; border-radius: 12px; font-size: 1.75rem; font-weight: 700; color: var(--success); letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 2rem; box-shadow: inset 0 0 10px rgba(0,0,0,0.5);">
                    <span id="generatedNumberText">+{{ session('generated_number') }}</span>
                    <button onclick="copyNumber()" class="btn btn-outline" style="padding: 0.5rem 0.85rem; font-size: 0.95rem; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" id="copyBtn">
                        <i class="fa-solid fa-copy" id="copyIcon"></i> <span id="copyText">Copy</span>
                    </button>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="{{ route('numbers.page') }}" class="btn btn-primary" style="flex: 1; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-mobile-screen"></i> View Active Numbers
                    </a>
                    <button onclick="closeModal()" class="btn btn-outline" style="flex: 1;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>


        @if(session('generated_number'))
            function closeModal() {
                const modal = document.getElementById('successModal');
                if (modal) {
                    modal.classList.remove('active');
                }
            }
            
            function copyNumber() {
                const numberText = "{{ session('generated_number') }}";
                navigator.clipboard.writeText(numberText).then(() => {
                    const copyBtn = document.getElementById('copyBtn');
                    const copyIcon = document.getElementById('copyIcon');
                    const copyText = document.getElementById('copyText');
                    
                    if (copyBtn && copyIcon && copyText) {
                        copyBtn.style.background = 'rgba(16, 185, 129, 0.15)';
                        copyBtn.style.borderColor = 'var(--success)';
                        copyIcon.className = 'fa-solid fa-check';
                        copyIcon.style.color = 'var(--success)';
                        copyText.textContent = 'Copied!';
                        
                        setTimeout(() => {
                            copyBtn.style.background = 'transparent';
                            copyBtn.style.borderColor = 'var(--border-color)';
                            copyIcon.className = 'fa-solid fa-copy';
                            copyIcon.style.color = 'inherit';
                            copyText.textContent = 'Copy';
                        }, 2000);
                    }
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        @endif
    </script>
@endsection
