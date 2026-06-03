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
                <p>{{ $totalNumbers }}</p>
            </div>
        </div>
        
        <div class="stat-card glass">
            <div class="stat-icon blue">
                <i class="fa-solid fa-signal"></i>
            </div>
            <div class="stat-info">
                <h3>Active Numbers</h3>
                <p>{{ $activeCount }}</p>
            </div>
        </div>

        <div class="stat-card glass">
            <div class="stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-info">
                <h3>Success Rate</h3>
                <p>{{ $successRate }}%</p>
            </div>
        </div>
        
        <div class="stat-card glass">
            <div class="stat-icon purple">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <div class="stat-info">
                <h3>Avg Health Score</h3>
                <p>{{ $avgHealthScore }}/100</p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem; align-items: start;">
        <!-- Left: Recent Activity -->
        <div class="panel-card glass" style="margin-bottom: 0;">
            <div class="panel-header">
                <h2 class="panel-title">Recent Numbers</h2>
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
                                <th>Quality</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($numbers->take(5) as $num)
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);">+{{ $num->number }}</td>
                                <td><span class="badge" style="background: rgba(139,92,246,0.1); color: #8b5cf6; text-transform: capitalize;">{{ $num->service }}</span></td>
                                <td>{{ $num->country->name ?? 'Unknown' }}</td>
                                <td>
                                    @if($num->status === 'active')
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">Active</span>
                                    @else
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">Closed</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $score = $num->reputation_score;
                                        $color = 'var(--success)';
                                        if ($score < 50) $color = 'var(--danger)';
                                        elseif ($score < 80) $color = 'var(--warning)';
                                    @endphp
                                    <span style="font-weight: 600; color: {{ $color }}">{{ $score }}/100</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Right: Country Stats & Audit Logs -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Country Statistics -->
            <div class="panel-card glass" style="margin-bottom: 0;">
                <div class="panel-header">
                    <h2 class="panel-title">Country Statistics</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @if(empty($countryStats))
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">No data available.</p>
                    @else
                        @foreach($countryStats as $stat)
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600; font-size: 0.95rem;">{{ $stat['name'] }}</span>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $stat['total'] }} Generated</span>
                                </div>
                                <span class="badge" style="font-weight: 700; background: rgba(37, 99, 235, 0.08); color: var(--accent);">
                                    {{ $stat['rate'] }}% Success
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Detailed Audit Logs -->
            <div class="panel-card glass" style="margin-bottom: 0;">
                <div class="panel-header">
                    <h2 class="panel-title">System Audit Logs</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 350px; overflow-y: auto;">
                    @if($auditLogs->isEmpty())
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">No logs available.</p>
                    @else
                        @foreach($auditLogs as $log)
                            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; font-size: 0.85rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                    <span style="font-weight: 600; color: var(--accent);">{{ $log->action }}</span>
                                    <span style="color: var(--text-secondary); font-size: 0.75rem;">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <p style="color: var(--text-primary); line-height: 1.3;">{{ $log->description }}</p>
                                <span style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">IP: {{ $log->ip_address }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
