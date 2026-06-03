@extends('user.layout')

@section('title', 'My Numbers')
@section('header', 'Active Virtual Numbers')

@section('content')
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($numbers as $num)
                        <tr>
                            <td style="font-weight: 700; color: var(--accent); font-size: 1.1rem; letter-spacing: 1px;">{{ $num->number }}</td>
                            <td><span class="badge" style="background: rgba(139,92,246,0.1); color: #8b5cf6;"><i class="fa-brands fa-{{ $num->service == 'other' ? 'app-store' : $num->service }}"></i> {{ ucfirst($num->service) }}</span></td>
                            <td><i class="fa-solid fa-location-dot" style="color:var(--text-secondary); margin-right:5px;"></i> {{ $num->country->name ?? 'Unknown' }}</td>
                            <td>
                                @if($num->status === 'active')
                                    <span class="badge"><i class="fa-solid fa-check"></i> Active</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">Closed</span>
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">{{ $num->created_at->diffForHumans() }}</td>
                            <td>
                                <button onclick="checkSms('{{ $num->id }}')" class="btn btn-success" style="padding: 0.4rem 1rem; font-size: 0.85rem;">
                                    <i class="fa-solid fa-inbox"></i> Read SMS
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Live SMS Modal -->
    <div class="modal-overlay" id="smsModal">
        <div class="modal-content glass">
            <div class="modal-header">
                <h2 style="display:flex; align-items:center; gap:10px;"><i class="fa-solid fa-satellite-dish" style="color:var(--accent)"></i> Live SMS Terminal</h2>
                <button class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.9rem;"><i class="fa-solid fa-circle-notch fa-spin"></i> Polling server for incoming messages...</p>
            <div class="sms-display" id="smsDisplay">Initializing connection...</div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function checkSms(id) {
        const modal = document.getElementById('smsModal');
        const display = document.getElementById('smsDisplay');
        
        modal.classList.add('active');
        display.innerHTML = '<span style="color:var(--text-secondary)">Establishing secure connection to Zyla API...</span>';

        fetch(`/app/sms/${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    display.innerHTML = '<i class="fa-solid fa-envelope-open-text"></i> ' + data.sms;
                    display.style.color = 'var(--success)';
                } else {
                    display.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.message;
                    display.style.color = 'var(--warning)';
                }
            })
            .catch(err => {
                display.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error connecting to terminal. Please try again.';
                display.style.color = 'var(--danger)';
            });
    }

    function closeModal() {
        document.getElementById('smsModal').classList.remove('active');
    }
</script>
@endsection
