@extends('user.layout')

@section('title', 'Overview')
@section('header', 'Dashboard Overview')

@section('content')
    <!-- Stats Row -->
    <div class="stats-grid">
        <div class="stat-card glass">
            <div class="stat-icon blue">
                <i class="fa-solid fa-hashtag"></i>
            </div>
            <div class="stat-info">
                <h3>Total Numbers</h3>
                <p>{{ $numbers->count() }}</p>
            </div>
        </div>
        
        <div class="stat-card glass">
            <div class="stat-icon green">
                <i class="fa-solid fa-message"></i>
            </div>
            <div class="stat-info">
                <h3>SMS Received</h3>
                <p>24</p> <!-- Mock data for visual appeal -->
            </div>
        </div>
        
        <div class="stat-card glass">
            <div class="stat-icon purple">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div class="stat-info">
                <h3>Success Rate</h3>
                <p>98.5%</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="panel-card glass">
        <div class="panel-header">
            <h2 class="panel-title">Recent Activity</h2>
            <a href="{{ route('numbers.page') }}" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.85rem;">View All</a>
        </div>
        
        @if($numbers->count() === 0)
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No activity yet. Generate your first number to see it here.</p>
                <a href="{{ route('generate.page') }}" class="btn btn-primary mt-4">Generate Number</a>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Service</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($numbers->take(5) as $num)
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);">{{ $num->number }}</td>
                            <td><span class="badge">{{ ucfirst($num->service) }}</span></td>
                            <td>{{ $num->country->name ?? 'Unknown' }}</td>
                            <td>
                                @if($num->status === 'active')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">Active</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">Closed</span>
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">{{ $num->created_at->format('M d, Y - H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
