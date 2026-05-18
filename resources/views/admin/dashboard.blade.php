@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

{{-- Welcome Banner --}}
<div class="vc-banner mb-4">
    <div>
        <div class="vc-banner-title">Welcome back, {{ auth()->user()->full_name }} 👋</div>
        <div class="vc-banner-sub">Here's a live overview of your election activity.</div>
    </div>
    <a href="{{ route('admin.sessions.create') }}" class="btn btn-light fw-semibold shadow-sm">
        <i class="bi bi-plus-lg me-1"></i>New Election
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="vc-stat">
            <div class="vc-stat-icon" style="background:#eff6ff;color:#1a56db"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="vc-stat-label">Total Students</div>
                <div class="vc-stat-value">{{ number_format($stats['total_students']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="vc-stat">
            <div class="vc-stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="vc-stat-label">Enrolled This Semester</div>
                <div class="vc-stat-value">{{ number_format($stats['enrollments']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="vc-stat" style="border-left:3px solid #f59e0b">
            <div class="vc-stat-icon" style="background:#fefce8;color:#ca8a04"><i class="bi bi-broadcast"></i></div>
            <div>
                <div class="vc-stat-label">Active Elections</div>
                <div class="vc-stat-value">{{ $stats['active_sessions'] }}</div>
                @if($stats['active_sessions'] > 0)
                    <div style="font-size:0.7rem;color:#22c55e;margin-top:2px">
                        <span class="live-dot me-1"></span>Live now
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="vc-stat">
            <div class="vc-stat-icon" style="background:#faf5ff;color:#7c3aed"><i class="bi bi-check2-square"></i></div>
            <div>
                <div class="vc-stat-label">Total Votes Cast</div>
                <div class="vc-stat-value">{{ number_format($stats['total_votes']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Main grid --}}
<div class="row g-4">

    {{-- LEFT column --}}
    <div class="col-lg-8 d-flex flex-column gap-4">

        {{-- Analytics row --}}
        <div class="row g-3">

            {{-- Donut: election status breakdown --}}
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
                    <div class="card-header bg-white py-3 px-4">
                        <strong style="font-size:0.9rem">Election Status</strong>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-2">
                        <div style="position:relative;width:130px;height:130px">
                            <canvas id="statusDonut" width="130" height="130"></canvas>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                                <div style="font-size:1.5rem;font-weight:700;line-height:1">{{ $stats['total_sessions'] }}</div>
                                <div style="font-size:0.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Total</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3" style="font-size:0.75rem">
                            @foreach([
                                ['color'=>'#22c55e','label'=>'Active',    'key'=>'active'],
                                ['color'=>'#f59e0b','label'=>'Scheduled', 'key'=>'scheduled'],
                                ['color'=>'#1a56db','label'=>'Completed', 'key'=>'completed'],
                                ['color'=>'#e2e8f0','label'=>'Other',     'key'=>'other'],
                            ] as $leg)
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:10px;height:10px;border-radius:50%;background:{{ $leg['color'] }};display:inline-block;flex-shrink:0"></span>
                                {{ $leg['label'] }}
                                @if($leg['key'] !== 'other')
                                    <span class="text-muted">({{ $statusCounts->get($leg['key'], 0) }})</span>
                                @endif
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bar: votes cast per recent election --}}
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <strong style="font-size:0.9rem">Votes Cast</strong>
                        <span class="badge bg-light text-secondary border" style="font-size:0.7rem">Recent elections</span>
                    </div>
                    <div class="card-body pt-2 pb-3 px-4">
                        @forelse($recentSessions->take(4) as $s)
                        @php
                            $cast = $s->dash_votes_cast;
                            // Use a relative bar: max cast among the set
                            $maxCast = $recentSessions->take(4)->max('dash_votes_cast');
                            $barPct  = $maxCast > 0 ? round(($cast / $maxCast) * 100) : 0;
                            $barColor = match($s->status) {
                                'active'    => '#22c55e',
                                'completed' => '#1a56db',
                                'scheduled' => '#f59e0b',
                                default     => '#94a3b8',
                            };
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-medium text-truncate" style="max-width:160px" title="{{ $s->title }}">
                                    {{ $s->title }}
                                </span>
                                <span class="small text-muted ms-2 flex-shrink-0">
                                    {{ number_format($cast) }} vote{{ $cast !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <div class="progress" style="height:8px;border-radius:4px;background:#f1f5f9">
                                <div class="progress-bar" style="width:{{ $barPct }}%;background:{{ $barColor }};border-radius:4px;transition:width 1s ease"></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4" style="font-size:0.85rem">
                            <i class="bi bi-bar-chart d-block fs-2 opacity-25 mb-2"></i>No data yet
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Elections table --}}
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
                <strong>Recent Elections</strong>
                <a href="{{ route('admin.sessions.index') }}" class="text-primary small text-decoration-none">View all →</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Status</th>
                            <th>Votes Cast</th>
                            <th>Period</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentSessions as $s)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-medium">{{ $s->title }}</div>
                            <div class="text-muted small text-capitalize">{{ $s->category }}</div>
                        </td>
                        <td>
                            <span class="badge badge-status-{{ $s->status }} px-2 py-1" style="border-radius:6px;font-size:0.78rem">
                                @if($s->status === 'active')<span class="live-dot me-1"></span>@endif
                                {{ ucfirst($s->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ number_format($s->dash_votes_cast) }}</span>
                            <span class="text-muted small"> votes</span>
                        </td>
                        <td class="small text-muted">
                            {{ $s->start_date->format('M d') }} → {{ $s->end_date->format('M d, Y') }}
                        </td>
                        <td>
                            <a href="{{ route('admin.sessions.show', $s) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
                            No elections yet. <a href="{{ route('admin.sessions.create') }}">Create your first →</a>
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- RIGHT sidebar --}}
    <div class="col-lg-4 d-flex flex-column gap-3">

        {{-- Live Elections --}}
        @if($activeSessions->count())
        <div class="card border-0 shadow-sm" style="border-radius:12px;border-left:4px solid #22c55e !important">
            <div class="card-header bg-white py-3 px-4">
                <strong class="text-success">
                    <span class="live-dot me-1"></span>Live Now ({{ $activeSessions->count() }})
                </strong>
            </div>
            <div class="list-group list-group-flush">
                @foreach($activeSessions as $active)
                <a href="{{ route('admin.sessions.results', $active) }}"
                   class="list-group-item list-group-item-action py-3 px-4">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="small fw-semibold text-dark">{{ $active->title }}</div>
                        <span class="badge bg-success ms-2 flex-shrink-0">{{ $active->dash_turnout_pct }}%</span>
                    </div>
                    <div class="progress mb-1" style="height:5px;border-radius:3px;background:#f1f5f9">
                        <div class="progress-bar bg-success" style="width:{{ $active->dash_turnout_pct }}%;border-radius:3px;transition:width 1s ease"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted" style="font-size:0.72rem">
                        <span>{{ number_format($active->dash_votes_cast) }} / {{ number_format($active->dash_total_voters) }} voters</span>
                        <span>Ends {{ $active->end_date->diffForHumans() }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Quick Actions --}}
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-header bg-white py-3 px-4"><strong>Quick Actions</strong></div>
            <div class="card-body d-grid gap-2 px-4 pb-4">
                <a href="{{ route('admin.enrollment.index') }}" class="btn btn-outline-primary text-start">
                    <i class="bi bi-upload me-2"></i>Upload Enrollment
                </a>
                <a href="{{ route('admin.sessions.create') }}" class="btn btn-outline-success text-start">
                    <i class="bi bi-plus-circle me-2"></i>Create Election
                </a>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline-secondary text-start">
                    <i class="bi bi-list-ul me-2"></i>All Elections
                </a>
            </div>
        </div>

        {{-- Status breakdown panel --}}
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-header bg-white py-3 px-4"><strong>Elections by Status</strong></div>
            <div class="card-body px-4 pb-3 pt-2">
                @foreach([
                    ['label'=>'Active',    'key'=>'active',    'color'=>'#22c55e','bg'=>'#f0fdf4'],
                    ['label'=>'Scheduled', 'key'=>'scheduled', 'color'=>'#f59e0b','bg'=>'#fffbeb'],
                    ['label'=>'Completed', 'key'=>'completed', 'color'=>'#1a56db','bg'=>'#eff6ff'],
                    ['label'=>'Paused',    'key'=>'paused',    'color'=>'#94a3b8','bg'=>'#f8fafc'],
                    ['label'=>'Cancelled', 'key'=>'cancelled', 'color'=>'#ef4444','bg'=>'#fef2f2'],
                ] as $item)
                <div class="d-flex justify-content-between align-items-center py-2"
                     style="border-bottom:1px solid #f1f5f9">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:10px;height:10px;border-radius:50%;background:{{ $item['color'] }};display:inline-block;flex-shrink:0"></span>
                        <span class="small">{{ $item['label'] }}</span>
                    </div>
                    <a href="{{ route('admin.sessions.index', ['status' => $item['key']]) }}"
                       class="badge rounded-pill text-decoration-none"
                       style="background:{{ $item['bg'] }};color:{{ $item['color'] }};border:1px solid {{ $item['color'] }}33">
                        {{ $statusCounts->get($item['key'], 0) }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Getting started tip --}}
        <div class="vc-tip">
            <div class="vc-tip-title"><i class="bi bi-lightbulb-fill me-1 text-warning"></i>Getting Started</div>
            <ol class="mb-0 ps-3 small" style="line-height:2">
                <li>Upload an enrollment Excel file</li>
                <li>Create an election &amp; set who can vote</li>
                <li>Add positions &amp; assign candidates</li>
                <li>Set status to <strong>Active</strong> to open voting</li>
                <li>View live results as votes come in</li>
            </ol>
        </div>
    </div>
</div>

<style>
.vc-banner {
    background: linear-gradient(135deg,#1a56db,#7c3aed);
    border-radius: 14px; padding: 1.4rem 1.75rem;
    display: flex; justify-content: space-between; align-items: center; color: #fff;
}
.vc-banner-title { font-size: 1.2rem; font-weight: 700; }
.vc-banner-sub   { font-size: 0.85rem; opacity: .75; margin-top: 2px; }

.vc-stat {
    background: #fff; border-radius: 12px;
    padding: 1rem 1.25rem; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 1rem;
}
.vc-stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.vc-stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.vc-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }

.vc-tip { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:1rem 1.25rem; }
.vc-tip-title { font-weight:600; font-size:.85rem; margin-bottom:.5rem; }

.live-dot {
    display: inline-block; width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e; animation: pulse-dot 1.5s infinite; vertical-align: middle;
}
@keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.3); }
}
</style>

@push('scripts')
<script>
(function () {
    const canvas = document.getElementById('statusDonut');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // Data injected safely from PHP — never user input
    const slices = [
        { value: {{ (int) $statusCounts->get('active', 0) }},    color: '#22c55e' },
        { value: {{ (int) $statusCounts->get('scheduled', 0) }},  color: '#f59e0b' },
        { value: {{ (int) $statusCounts->get('completed', 0) }},  color: '#1a56db' },
        { value: {{ (int) $stats['total_sessions'] - $statusCounts->only(['active','scheduled','completed'])->sum() }}, color: '#e2e8f0' },
    ];

    const total = slices.reduce((s, d) => s + d.value, 0);

    if (total === 0) {
        ctx.beginPath();
        ctx.arc(65, 65, 48, 0, Math.PI * 2);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth   = 14;
        ctx.stroke();
        return;
    }

    let start = -Math.PI / 2;
    slices.forEach(d => {
        if (d.value <= 0) return;
        const angle = (d.value / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(65, 65);
        ctx.arc(65, 65, 48, start, start + angle);
        ctx.closePath();
        ctx.fillStyle = d.color;
        ctx.fill();
        start += angle;
    });

    // Punch hole
    ctx.beginPath();
    ctx.arc(65, 65, 34, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();
})();
</script>
@endpush

@endsection
