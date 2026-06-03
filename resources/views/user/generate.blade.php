@extends('user.layout')

@section('title', 'Generate Number')
@section('header', 'Acquire New Number')

@section('content')
    <div class="panel-card glass" style="max-width: 600px; margin: 0 auto;">
        <h2 class="panel-title">Select Parameters</h2>
        <form action="{{ route('generate.number') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-globe" style="color:var(--accent); margin-right:5px;"></i> Target Country</label>
                <select name="country_id" class="form-control" required>
                    <option value="">-- Choose Country --</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-brands fa-app-store-ios" style="color:var(--accent); margin-right:5px;"></i> Application / Service</label>
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

            <div class="alert" style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); display: flex; gap: 15px; margin-top: 2rem;">
                <i class="fa-solid fa-circle-info" style="color: var(--accent); font-size: 1.5rem;"></i>
                <div>
                    <h4 style="margin-bottom: 5px;">Cost Estimation</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">The cost of this number is dynamically calculated based on the selected country and service. The amount will be deducted from your wallet upon successful SMS reception.</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                <i class="fa-solid fa-satellite-dish"></i> Connect & Generate Number
            </button>
        </form>
    </div>
@endsection
