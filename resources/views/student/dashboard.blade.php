<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — My Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', system-ui, sans-serif; }

        .topnav {
            background: #1a56db;
            padding: 0.9rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .topnav .brand { color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: -0.5px; }
        .topnav .brand span { color: #93c5fd; }

        .profile-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            transition: all 0.3s;
        }

        .election-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
        }
        .election-card:hover { box-shadow: 0 6px 20px rgba(26,86,219,0.12); transform: translateY(-2px); }

        .election-card .accent-bar {
            height: 4px;
            background: #1a56db;
            transition: width 0.5s ease;
        }
        .election-card.voted .accent-bar { background: #22c55e; }
        .election-card.completed .accent-bar { background: #8b5cf6; }

        .btn-vote-now {
            background: #1a56db; color: #fff; border: none;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-block;
        }
        .btn-vote-now:hover { background: #1447c0; color: #fff; transform: translateY(-1px); }

        .btn-view-results {
            background: #8b5cf6; color: #fff; border: none;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-block;
        }
        .btn-view-results:hover { background: #7c3aed; color: #fff; transform: translateY(-1px); }

        .btn-view-receipt {
            background: #f0f4ff; color: #1a56db; border: 1px solid #1a56db;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-block;
        }
        .btn-view-receipt:hover { background: #1a56db; color: #fff; }

        .section-title {
            font-weight: 700; font-size: 1rem; color: #1e293b;
            display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;
        }
        .section-title .dot {
            width: 10px; height: 10px; border-radius: 50%;
        }
        .dot-active  { background: #22c55e; animation: pulse 1.5s infinite; }
        .dot-voted   { background: #94a3b8; }
        .dot-completed { background: #8b5cf6; }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.2); }
        }

        .empty-state {
            text-align: center; padding: 2.5rem 1rem; color: #94a3b8;
            background: #fff; border-radius: 14px; border: 1px dashed #e2e8f0;
        }
        .empty-state i { font-size: 2.5rem; opacity: 0.3; display: block; margin-bottom: 0.75rem; }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.7rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .candidate-result-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .candidate-result-card.winner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }

        .candidate-result-card.leading {
            background: #e0e7ff;
            border-left: 4px solid #1a56db;
        }

        .progress-bar-custom {
            transition: width 0.5s ease;
        }

        .winner-crown {
            color: #f59e0b;
            animation: bounce 0.5s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .refresh-btn {
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            transform: rotate(180deg);
        }

        .live-badge {
            background: #ef4444;
            color: white;
            animation: pulse 1s infinite;
        }

        .results-modal .modal-content {
            border-radius: 20px;
            max-height: 80vh;
        }

        .results-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .turnout-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .turnout-circle span {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .election-card {
            animation: slideIn 0.3s ease-out;
        }

        .badge-live {
            background: #dcfce7;
            color: #15803d;
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <div class="d-flex gap-3 align-items-center">
        <span class="text-white small">
            <i class="bi bi-person-circle me-1"></i>{{ $user->full_name }}
        </span>
        <form method="POST" action="{{ route('student.logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>

<div class="container py-4" style="max-width:1200px">

    {{-- Flash messages --}}
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left Column: Profile & Stats --}}
        <div class="col-lg-4">
            {{-- Profile Card --}}
            <div class="profile-card mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg, #1a56db, #3b82f6);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;font-weight:700">
                            {{ strtoupper(substr($user->full_name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="col">
                        <div style="font-size:1.1rem;font-weight:700;color:#1e293b">{{ $user->full_name }}</div>
                        <div style="font-size:0.85rem;color:#64748b">
                            <i class="bi bi-card-text me-1"></i>{{ $user->student_id }}
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-primary">{{ $user->department }}</span>
                            <span class="badge bg-secondary">Year {{ $user->year_level }}</span>
                            <span class="badge bg-info">{{ $user->section }}</span>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row text-center">
                    <div class="col-6">
                        <div class="stats-number text-primary">{{ $pendingSessions->count() }}</div>
                        <div class="stats-label">Pending Votes</div>
                    </div>
                    <div class="col-6">
                        <div class="stats-number text-success">{{ $votedSessions->count() }}</div>
                        <div class="stats-label">Votes Cast</div>
                    </div>
                </div>
            </div>

            {{-- Quick Stats Card --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Your Voting Activity</h6>
                    @php
                        $totalVotesCast = $votedSessions->count();
                        $totalEligible = $pendingSessions->count() + $votedSessions->count();
                        $completionRate = $totalEligible > 0 ? round(($totalVotesCast / $totalEligible) * 100) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Completion Rate</span>
                            <span class="fw-bold">{{ $completionRate }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-success" style="width: {{ $completionRate }}%; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6">
                            <div class="text-success fw-bold">{{ $totalVotesCast }}</div>
                            <div class="small text-muted">Votes Cast</div>
                        </div>
                        <div class="col-6">
                            <div class="text-warning fw-bold">{{ $pendingSessions->count() }}</div>
                            <div class="small text-muted">Remaining</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Elections --}}
        <div class="col-lg-8">
            {{-- Available Elections --}}
            <div class="section-title">
                <span class="dot dot-active"></span>
                Available Elections
                @if($pendingSessions->count())
                    <span class="badge bg-primary rounded-pill" style="font-size:0.75rem; animation: pulse 1s infinite;">{{ $pendingSessions->count() }} pending</span>
                @endif
            </div>

            @forelse($pendingSessions as $session)
            <div class="election-card mb-3" data-session-id="{{ $session->id }}">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:700;color:#1e293b;font-size:1.1rem">{{ $session->title }}</div>
                                @if($session->end_date->diffInHours(now()) < 24)
                                    <span class="badge badge-live">
                                        <i class="bi bi-stopwatch me-1"></i>Ending soon
                                    </span>
                                @endif
                            </div>
                            @if($session->description)
                                <div style="font-size:0.85rem;color:#64748b;margin-bottom:0.5rem">{{ Str::limit($session->description, 100) }}</div>
                            @endif
                            <div class="d-flex gap-3 flex-wrap">
                                <div style="font-size:0.8rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ends {{ $session->end_date->format('M d, Y h:i A') }}
                                </div>
                                <div style="font-size:0.8rem;color:#94a3b8">
                                    <i class="bi bi-person-badge me-1"></i>{{ $session->positions->count() }} position(s)
                                </div>
                                <div style="font-size:0.8rem;color:#94a3b8">
                                    <i class="bi bi-clock me-1"></i>{{ $session->end_date->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <a href="{{ route('student.ballot', $session) }}" class="btn-vote-now">
                                Vote Now <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                            <span><i class="bi bi-info-circle me-1"></i>Complete all {{ $session->positions->count() }} positions</span>
                            <span>0/{{ $session->positions->count() }} completed</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state mb-4">
                <i class="bi bi-ballot"></i>
                <h6 class="mt-2">No elections available</h6>
                <p class="small mb-0">Check back later for new elections you can participate in.</p>
            </div>
            @endforelse

            {{-- Voted but Still Active (Live Results) --}}
            @if($votedSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-voted"></span>
                Live Election Results
                <span class="badge bg-success rounded-pill">
                    <i class="bi bi-eye me-1"></i>Real-time
                </span>
            </div>

            @foreach($votedSessions as $session)
            <div class="election-card voted mb-3" data-session-id="{{ $session->id }}">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge" style="background:#dcfce7;color:#166534;">
                                    <i class="bi bi-check2-circle me-1"></i>Voted
                                </span>
                                <span class="badge live-badge">
                                    <i class="bi bi-broadcast me-1"></i>LIVE
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ends {{ $session->end_date->format('M d, Y h:i A') }}
                                </div>
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div class="ms-3 d-flex gap-2">
                            <button class="btn-view-results" onclick="showLiveResults({{ $session->id }})">
                                <i class="bi bi-bar-chart-fill me-1"></i>Live Results
                            </button>
                            <button class="btn-view-receipt" onclick="viewReceipt({{ $session->id }})">
                                <i class="bi bi-receipt me-1"></i>Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif

            {{-- Completed Sessions with Final Results --}}
            @if(isset($completedSessions) && $completedSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-completed"></span>
                Completed Elections
                <span class="badge bg-secondary rounded-pill">Final Results</span>
            </div>

            @foreach($completedSessions as $session)
            <div class="election-card completed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge" style="background:#e9d5ff;color:#6b21a5;">
                                    <i class="bi bi-trophy me-1"></i>Completed
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <button class="btn-view-results" onclick="showFinalResults({{ $session->id }})">
                                <i class="bi bi-trophy me-1"></i>View Winners
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Live Results Modal --}}
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content results-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-bar-chart-fill me-2"></i>
                    <span id="resultsModalTitle">Live Election Results</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultsModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading results...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary refresh-btn" onclick="refreshCurrentResults()">
                    <i class="bi bi-arrow-repeat me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content receipt-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a56db 0%, #1447c0 100%); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-receipt me-2"></i>Vote Receipt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading receipt...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentSessionId = null;
    let autoRefreshInterval = null;

    // Show live results
    async function showLiveResults(sessionId) {
        currentSessionId = sessionId;
        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        const content = document.getElementById('resultsModalContent');

        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading live results...</p>
            </div>
        `;

        modal.show();
        await loadResults(sessionId);

        // Auto-refresh every 10 seconds for live results
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        autoRefreshInterval = setInterval(() => loadResults(sessionId, true), 10000);
    }

    // Show final results (no auto-refresh)
    async function showFinalResults(sessionId) {
        currentSessionId = sessionId;
        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        const content = document.getElementById('resultsModalContent');

        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading final results...</p>
            </div>
        `;

        modal.show();
        await loadResults(sessionId, false);

        // Clear auto-refresh for final results
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    // Load results from API
    async function loadResults(sessionId, isLive = true) {
        try {
            const response = await fetch(`/results/${sessionId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) throw new Error('Failed to load results');

            const data = await response.json();
            if (!data.success) throw new Error(data.error);

            document.getElementById('resultsModalTitle').innerHTML = `
                <i class="bi ${isLive ? 'bi-broadcast' : 'bi-trophy'} me-2"></i>
                ${escapeHtml(data.session_title)}
                <span class="badge ${isLive ? 'bg-danger' : 'bg-warning'} ms-2">
                    ${isLive ? 'LIVE UPDATES' : 'FINAL RESULTS'}
                </span>
            `;

            const turnoutColor = data.turnout >= 50 ? 'success' : (data.turnout >= 25 ? 'warning' : 'danger');

            let html = `
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3">
                            <div class="small text-muted">Total Voters</div>
                            <div class="h3 mb-0 fw-bold">${data.total_voters.toLocaleString()}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3">
                            <div class="small text-muted">Votes Cast</div>
                            <div class="h3 mb-0 fw-bold text-success">${data.total_voted.toLocaleString()}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3">
                            <div class="small text-muted">Turnout</div>
                            <div class="h3 mb-0 fw-bold text-${turnoutColor}">${data.turnout}%</div>
                        </div>
                    </div>
                </div>

                <div class="progress mb-4" style="height: 8px;">
                    <div class="progress-bar bg-${turnoutColor}" style="width: ${data.turnout}%"></div>
                </div>

                <div class="text-center mb-3">
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i>Last updated: ${new Date(data.last_update).toLocaleTimeString()}
                        ${isLive ? '<span class="ms-2 text-success"><i class="bi bi-circle-fill fs-6"></i> Auto-refreshing</span>' : ''}
                    </small>
                </div>
            `;

            for (const position of data.results) {
                html += `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>
                                    ${escapeHtml(position.title)}
                                    ${position.max_winners > 1 ? `<span class="badge bg-info ms-2">${position.max_winners} winner(s)</span>` : ''}
                                </h6>
                                <span class="badge bg-secondary">${position.total_votes} total votes</span>
                            </div>
                        </div>
                        <div class="card-body">
                `;

                for (const candidate of position.candidates) {
                    const isWinner = candidate.is_winner && position.max_winners === 1;
                    const isLeading = candidate.is_winner && position.max_winners > 1;

                    html += `
                        <div class="candidate-result-card mb-2 ${isWinner ? 'winner' : (isLeading ? 'leading' : '')}">
                            <div class="d-flex align-items-center gap-3">
                                <img src="${candidate.photo}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;" alt="">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                ${escapeHtml(candidate.name)}
                                                ${isWinner ? '<span class="winner-crown ms-1">🏆</span>' : ''}
                                                ${isLeading ? '<span class="ms-1 text-primary">⭐ Leading</span>' : ''}
                                            </div>
                                            <div class="small text-muted">${escapeHtml(candidate.section)}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold h5 mb-0">${candidate.vote_count}</div>
                                            <div class="small text-muted">${candidate.percentage}%</div>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar progress-bar-custom ${isWinner ? 'bg-success' : 'bg-primary'}"
                                             style="width: ${candidate.percentage}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (position.candidates.length === 0) {
                    html += `<p class="text-muted text-center py-3">No candidates for this position.</p>`;
                }

                html += `</div></div>`;
            }

            document.getElementById('resultsModalContent').innerHTML = html;

        } catch (error) {
            console.error('Error loading results:', error);
            document.getElementById('resultsModalContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                    <p class="mt-3 text-danger fw-bold">Failed to load results</p>
                    <p class="small text-muted">${escapeHtml(error.message)}</p>
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="loadResults(${sessionId}, ${isLive})">
                        <i class="bi bi-arrow-repeat me-1"></i>Try Again
                    </button>
                </div>
            `;
        }
    }

    // Refresh current results
    function refreshCurrentResults() {
        if (currentSessionId) {
            const isLive = document.getElementById('resultsModalTitle').innerHTML.includes('LIVE');
            loadResults(currentSessionId, isLive);
        }
    }

    // View receipt function
    async function viewReceipt(sessionId) {
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        const receiptContent = document.getElementById('receiptContent');

        receiptContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading receipt...</p>
            </div>
        `;

        modal.show();

        try {
            const response = await fetch(`/receipt/${sessionId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) throw new Error('Failed to load receipt');

            const data = await response.json();
            const votedDate = new Date(data.voted_at);
            const formattedDate = votedDate.toLocaleString();

            receiptContent.innerHTML = `
                <div class="receipt-id-box text-center mb-3" style="background: #f0f4ff; border-radius: 12px; padding: 1rem;">
                    <div class="small text-muted mb-1">Receipt ID</div>
                    <div class="fw-bold" style="font-family: monospace; font-size: 1.1rem;">${escapeHtml(data.receipt_id)}</div>
                    <div class="small text-muted mt-1">${formattedDate}</div>
                </div>

                <h6 class="fw-bold mb-3">Your Votes for: ${escapeHtml(data.session_title)}</h6>

                ${data.votes.map(vote => `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">${escapeHtml(vote.position)}</div>
                            <div class="small text-muted">${escapeHtml(vote.candidate)}</div>
                        </div>
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    </div>
                `).join('')}

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    This receipt confirms your vote was securely recorded.
                </div>
            `;

        } catch (error) {
            receiptContent.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                    <p class="mt-3 text-danger fw-bold">Failed to load receipt</p>
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="viewReceipt(${sessionId})">
                        <i class="bi bi-arrow-repeat me-1"></i>Try Again
                    </button>
                </div>
            `;
        }
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Clean up interval when modal is closed
    document.getElementById('resultsModal').addEventListener('hidden.bs.modal', function () {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });

    // Add animation on page load
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.election-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>
</body>
</html>
