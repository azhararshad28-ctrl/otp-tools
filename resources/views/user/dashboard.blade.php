<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Dashboard</title>
    <link rel="stylesheet" href="/css/premium.css">
</head>
<body>
    <nav class="navbar glass">
        <div class="nav-brand">⚡ Premium OTP Hub</div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Log Out</button>
        </form>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="dashboard-grid">
            
            <!-- Generate Panel -->
            <div class="panel-card glass">
                <h2 class="panel-title">Generate New Number</h2>
                <form action="{{ route('generate.number') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Select Target Country</label>
                        <select name="country_id" class="form-control" required>
                            <option value="">-- Choose Country --</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Service / App</label>
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

                    <button type="submit" class="btn btn-primary mt-4">Generate Virtual Number</button>
                </form>
            </div>

            <!-- Numbers Panel -->
            <div class="panel-card glass">
                <h2 class="panel-title">My Active Numbers</h2>
                
                @if($numbers->count() === 0)
                    <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No active numbers found. Generate one to get started!</p>
                @else
                    <div class="numbers-list">
                        @foreach($numbers as $number)
                        <div class="number-item">
                            <div class="number-details">
                                <h3>{{ $number->number }}</h3>
                                <div class="number-meta">
                                    <span class="badge">{{ ucfirst($number->service) }}</span>
                                    {{ $number->country->name ?? 'Unknown' }} • Generated {{ $number->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <button onclick="checkSms('{{ $number->id }}')" class="btn btn-success">Check SMS</button>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- SMS Modal -->
    <div class="modal-overlay" id="smsModal">
        <div class="modal-content glass">
            <div class="modal-header">
                <h2>Live SMS Feed</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Polling server for new messages on this number...</p>
            <div class="sms-display" id="smsDisplay">Loading...</div>
        </div>
    </div>

    <script>
        function checkSms(id) {
            const modal = document.getElementById('smsModal');
            const display = document.getElementById('smsDisplay');
            
            modal.classList.add('active');
            display.innerText = 'Connecting to Zyla API...';

            fetch(`/app/sms/${id}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        display.innerText = data.sms;
                        display.style.color = 'var(--success)';
                    } else {
                        display.innerText = data.message;
                        display.style.color = 'var(--text-primary)';
                    }
                })
                .catch(err => {
                    display.innerText = 'Error connecting to API. Please try again.';
                    display.style.color = 'var(--danger)';
                });
        }

        function closeModal() {
            document.getElementById('smsModal').classList.remove('active');
        }
    </script>
</body>
</html>
