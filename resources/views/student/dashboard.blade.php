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
        .election-card.missed .accent-bar { background: #ef4444; }

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

        .btn-view-missed {
            background: #fef3c7; color: #d97706; border: 1px solid #fcd34d;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-block;
        }
        .btn-view-missed:hover { background: #fde68a; color: #b45309; }

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
        .dot-missed { background: #ef4444; }

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

        .progress-bar-custom {
            transition: width 0.5s ease;
        }

        .winner-crown {
            color: #f59e0b;
            animation: bounce 0.5s ease;
            display: inline-block;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .live-badge {
            background: #ef4444;
            color: white;
            animation: pulse 1s infinite;
        }

        .missed-badge {
            background: #fee2e2;
            color: #dc2626;
        }

        .results-modal .modal-content {
            border-radius: 20px;
            max-height: 80vh;
        }

        .results-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.05); }
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
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.7rem;
            background: #ef4444;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            animation: pulse 1.5s infinite;
        }

        .last-update {
            font-size: 0.7rem;
            color: #64748b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .stat-label-small {
            font-size: 0.7rem;
            color: #64748b;
        }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <div class="d-flex gap-3 align-items-center">
        <a href="{{ route('profile.index') }}" class="text-white text-decoration-none d-flex align-items-center gap-2" style="opacity:0.92">
            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.4)">
            <span class="small d-none d-md-inline">{{ $user->full_name }}</span>
        </a>
        <form method="POST" action="{{ route('student.logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>

<div class="container py-4" style="max-width:1200px">

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
        <div class="col-lg-4">
            <div class="profile-card mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                             style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
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

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number text-primary">{{ $pendingSessions->count() }}</div>
                        <div class="stat-label-small">Pending</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-success">{{ $votedActiveSessions->count() }}</div>
                        <div class="stat-label-small">Active Voted</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-info">{{ $completedVotedSessions->count() }}</div>
                        <div class="stat-label-small">Completed Voted</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-danger">{{ $missedSessions->count() }}</div>
                        <div class="stat-label-small">Missed</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Your Voting Activity</h6>
                    @php
                        $totalParticipated = $votedActiveSessions->count() + $completedVotedSessions->count();
                        $totalEligible = $pendingSessions->count() + $totalParticipated + $missedSessions->count();
                        $participationRate = $totalEligible > 0 ? round(($totalParticipated / $totalEligible) * 100) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Participation Rate</span>
                            <span class="fw-bold">{{ $participationRate }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-success" style="width: {{ $participationRate }}%; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <div class="text-success fw-bold">{{ $totalParticipated }}</div>
                            <div class="small text-muted">Voted</div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning fw-bold">{{ $pendingSessions->count() }}</div>
                            <div class="small text-muted">Pending</div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger fw-bold">{{ $missedSessions->count() }}</div>
                            <div class="small text-muted">Missed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
            <div class="election-card mb-3">
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
                </div>
            </div>
            @empty
            <div class="empty-state mb-4">
                <i class="bi bi-ballot"></i>
                <h6 class="mt-2">No elections available</h6>
                <p class="small mb-0">Check back later for new elections you can participate in.</p>
            </div>
            @endforelse

            {{-- Voted Active Sessions --}}
            @if($votedActiveSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-voted"></span>
                Live Election Results
                <span class="live-indicator">
                    <i class="bi bi-broadcast me-1"></i>LIVE
                </span>
            </div>

            @foreach($votedActiveSessions as $session)
            <div class="election-card voted mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge" style="background:#dcfce7;color:#166534;">
                                    <i class="bi bi-check2-circle me-1"></i>Voted
                                </span>
                                <span class="live-badge" style="font-size:0.7rem; padding:0.2rem 0.5rem;">
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
                                <i class="bi bi-bar-chart-fill me-1"></i>View Live Results
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

            {{-- Completed Voted Sessions --}}
            @if($completedVotedSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-completed"></span>
                Completed Elections (You Voted)
                <span class="badge bg-secondary rounded-pill">Final Results</span>
            </div>

            @foreach($completedVotedSessions as $session)
            <div class="election-card completed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge" style="background:#e9d5ff;color:#6b21a5;">
                                    <i class="bi bi-check2-circle me-1"></i>Voted
                                </span>
                                <span class="badge" style="background:#f3e8ff;color:#6b21a5;">
                                    <i class="bi bi-trophy me-1"></i>Completed
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                                </div>
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div class="ms-3 d-flex gap-2">
                            <button class="btn-view-results" onclick="showFinalResults({{ $session->id }})">
                                <i class="bi bi-trophy me-1"></i>View Winners
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

            {{-- Missed Sessions --}}
            @if($missedSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-missed"></span>
                Missed Elections
                <span class="badge bg-danger rounded-pill">You did not vote</span>
            </div>

            @foreach($missedSessions as $session)
            <div class="election-card missed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge missed-badge">
                                    <i class="bi bi-x-circle me-1"></i>Missed
                                </span>
                                <span class="badge" style="background:#f3e8ff;color:#6b21a5;">
                                    <i class="bi bi-trophy me-1"></i>Completed
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-calendar-x me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                                </div>
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <button class="btn-view-missed" onclick="showFinalResults({{ $session->id }})">
                                <i class="bi bi-eye me-1"></i>View Results
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top">
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle"></i>
                            <span>You did not participate in this election. Results are available for viewing.</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Results Modal --}}
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content results-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-bar-chart-fill me-2"></i>
                    <span id="resultsModalTitle">Election Results</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="last-update text-white-50" id="modalLastUpdate"></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="resultsModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading results...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
    let isModalOpen = false;

    async function showLiveResults(sessionId) {
        currentSessionId = sessionId;
        isModalOpen = true;

        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        const content = document.getElementById('resultsModalContent');

        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading live results...</p>
            </div>
        `;

        modal.show();

        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        autoRefreshInterval = setInterval(() => loadResults(sessionId, true), 3000);

        await loadResults(sessionId, true);
    }

    async function showFinalResults(sessionId) {
        currentSessionId = sessionId;
        isModalOpen = true;

        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        const content = document.getElementById('resultsModalContent');

        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading final results...</p>
            </div>
        `;

        modal.show();

        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }

        await loadResults(sessionId, false);
    }

    async function loadResults(sessionId, isLive = true) {
        try {
            const response = await fetch(`/results/${sessionId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || 'Failed to load results');
            }

            const data = await response.json();
            if (!data.success) throw new Error(data.error);

            document.getElementById('resultsModalTitle').innerHTML = `
                <i class="bi ${isLive ? 'bi-broadcast' : 'bi-trophy'} me-2"></i>
                ${escapeHtml(data.session_title)}
                <span class="badge ${isLive ? 'bg-danger' : 'bg-warning'} ms-2">
                    ${isLive ? 'LIVE UPDATES (auto-refresh every 3s)' : 'FINAL RESULTS'}
                </span>
            `;

            const updateTime = new Date(data.last_update).toLocaleTimeString();
            document.getElementById('modalLastUpdate').innerHTML = `
                <i class="bi bi-clock me-1"></i>Last updated: ${updateTime}
                ${isLive ? '<span class="ms-1 text-warning">● LIVE</span>' : ''}
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
            `;

            for (const position of data.results) {
                const winnerCount = position.candidates.filter(c => c.is_winner).length;
                let winnerBadge = '';
                if (position.max_winners > 1 && winnerCount > 0) {
                    winnerBadge = `<span class="badge bg-success ms-2">🏆 ${winnerCount} Winner(s)</span>`;
                } else if (winnerCount > 0) {
                    winnerBadge = `<span class="badge bg-success ms-2">🏆 Winner</span>`;
                }

                html += `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-0">
                                        <i class="bi bi-person-badge me-2 text-primary"></i>
                                        ${escapeHtml(position.title)}
                                        ${position.max_winners > 1 ? `<span class="badge bg-info ms-2">${position.max_winners} winner(s)</span>` : ''}
                                        ${winnerBadge}
                                    </h6>
                                </div>
                                <span class="badge bg-secondary">${position.total_votes} total votes</span>
                            </div>
                        </div>
                        <div class="card-body">
                `;

                for (const candidate of position.candidates) {
                    const isWinner = candidate.is_winner;
                    const winnerClass = isWinner ? 'winner' : '';

                    html += `
                        <div class="candidate-result-card mb-2 ${winnerClass}">
                            <div class="d-flex align-items-center gap-3">
                                <img src="${candidate.photo}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;" alt="">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <div class="fw-semibold">
                                                ${escapeHtml(candidate.name)}
                                                ${isWinner ? '<span class="winner-crown ms-1">🏆</span>' : ''}
                                                ${isWinner && position.max_winners > 1 ? '<span class="badge bg-success ms-1">Winner</span>' : ''}
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

            if (isLive && autoRefreshInterval) {
                html += `
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            Auto-refreshing every 3 seconds
                        </small>
                    </div>
                `;
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

            if (data.has_votes === false || data.votes.length === 0) {
                receiptContent.innerHTML = `
                    <div class="receipt-id-box text-center mb-3" style="background: #f0f4ff; border-radius: 12px; padding: 1rem;">
                        <div class="small text-muted mb-1">Receipt ID</div>
                        <div class="fw-bold" style="font-family: monospace; font-size: 1.1rem;">${escapeHtml(data.receipt_id)}</div>
                        <div class="small text-muted mt-1">${formattedDate}</div>
                    </div>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-eye-slash me-2"></i>
                        <strong>You abstained from all positions</strong>
                        <div class="small mt-1">No votes were cast in this election. Your participation has been recorded.</div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        This receipt confirms your participation was securely recorded.
                    </div>
                `;
            } else {
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
                                <div class="small text-muted">${escapeHtml(vote.candidate_section)}</div>
                            </div>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        </div>
                    `).join('')}
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        This receipt confirms your vote was securely recorded.
                    </div>
                `;
            }

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

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.getElementById('resultsModal').addEventListener('hidden.bs.modal', function () {
        isModalOpen = false;
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.election-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>
</body>
</html>
