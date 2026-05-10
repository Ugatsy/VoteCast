<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — My Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: #f0f4ff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
            width: 100%;
        }

        .topnav {
            background: #1a56db;
            padding: 0.9rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .topnav .brand {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .topnav .brand span { color: #93c5fd; }

        .profile-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            transition: all 0.3s;
        }

        .election-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
            margin-bottom: 1rem;
        }

        .election-card:hover {
            box-shadow: 0 6px 20px rgba(26,86,219,0.12);
            transform: translateY(-2px);
        }

        .election-card .accent-bar {
            height: 4px;
            background: #1a56db;
            transition: width 0.5s ease;
        }

        .election-card.voted .accent-bar { background: #22c55e; }
        .election-card.completed .accent-bar { background: #8b5cf6; }
        .election-card.missed .accent-bar { background: #ef4444; }

        /* Mobile-friendly buttons */
        .btn-vote-now, .btn-view-results, .btn-view-receipt {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .btn-vote-now {
            background: #1a56db;
            color: #fff;
            border: none;
        }

        .btn-vote-now:hover {
            background: #1447c0;
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-view-results {
            background: #8b5cf6;
            color: #fff;
            border: none;
        }

        .btn-view-results:hover {
            background: #7c3aed;
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-view-receipt {
            background: #f0f4ff;
            color: #1a56db;
            border: 1px solid #1a56db;
        }

        .btn-view-receipt:hover {
            background: #1a56db;
            color: #fff;
        }

        .btn-qr-scanner {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-qr-scanner:hover {
            background: #059669;
            color: #fff;
        }

        .section-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .section-title .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-active { background: #22c55e; animation: pulse 1.5s infinite; }
        .dot-voted { background: #94a3b8; }
        .dot-completed { background: #8b5cf6; }
        .dot-missed { background: #ef4444; }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #94a3b8;
            background: #fff;
            border-radius: 14px;
            border: 1px dashed #e2e8f0;
        }

        .empty-state i {
            font-size: 2rem;
            opacity: 0.3;
            display: block;
            margin-bottom: 0.75rem;
        }

        .stats-number {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.65rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modal Styles */
        .qr-scanner-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .qr-scanner-modal .modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }

        .nav-tabs .nav-link.active {
            color: #10b981;
            border-bottom: 2px solid #10b981;
            background: transparent;
        }

        /* Results Modal */
        .results-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .results-container {
            max-height: 60vh;
            overflow-y: auto;
        }

        .results-container .list-group-item {
            border-left: none;
            border-right: none;
            border-radius: 0;
        }

        .results-container .list-group-item:first-child {
            border-top: none;
        }

        .last-update {
            font-size: 0.7rem;
        }

        /* Receipt Modal */
        .receipt-id-box {
            background: #f0f4ff;
            border: 2px dashed #93c5fd;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }

        .receipt-id-value {
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            font-weight: 800;
            color: #1a56db;
            letter-spacing: 1px;
            word-break: break-all;
        }

        .vote-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .vote-position {
            font-size: 0.8rem;
            color: #64748b;
            flex: 1;
        }

        .vote-candidate {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85rem;
            text-align: right;
        }

        .abstain-item {
            background: #fef3c7;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            color: #92400e;
        }

        /* Mobile Responsive Fixes */
        @media (max-width: 768px) {
            .topnav {
                padding: 0.75rem 1rem;
            }

            .topnav .brand {
                font-size: 1rem;
            }

            .profile-card {
                margin-bottom: 1rem;
            }

            .election-card .p-3 {
                padding: 1rem !important;
            }

            /* Fix for completed elections overlapping */
            .election-card .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem;
            }

            .election-card .ms-3 {
                margin-left: 0 !important;
                width: 100%;
            }

            .election-card .d-flex.gap-2 {
                width: 100%;
                justify-content: flex-start;
            }

            .btn-view-results, .btn-view-receipt {
                flex: 1;
                justify-content: center;
                font-size: 0.75rem;
                padding: 0.5rem;
            }

            .section-title {
                font-size: 0.9rem;
                margin-top: 1rem;
            }

            .stats-number {
                font-size: 1.2rem;
            }

            .stats-label {
                font-size: 0.6rem;
            }

            /* Fix for election info wrapping */
            .election-card .flex-grow-1 .d-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .election-card .d-flex.gap-3 {
                gap: 0.5rem !important;
            }

            /* Modal adjustments for mobile */
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .results-container {
                max-height: 50vh;
            }

            .vote-item {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .vote-candidate {
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .profile-card .row.g-3 {
                flex-direction: column;
                text-align: center;
            }

            .profile-card .col-auto {
                text-align: center;
            }

            .d-flex.justify-content-around {
                gap: 0.5rem;
            }

            .btn-vote-now, .btn-view-results, .btn-view-receipt {
                font-size: 0.7rem;
                padding: 0.4rem 0.6rem;
            }

            .btn-qr-scanner {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <div class="d-flex gap-2 align-items-center">
        <button class="btn-qr-scanner" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
            <i class="bi bi-qr-code-scan"></i>
            <span class="d-none d-sm-inline">Scan QR</span>
        </button>
        <a href="{{ route('profile.index') }}" class="text-white text-decoration-none d-flex align-items-center gap-2" style="opacity:0.92">
            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.4)">
            <span class="small d-none d-md-inline">{{ $user->full_name }}</span>
        </a>
        <form method="POST" action="{{ route('student.logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-sm-inline">Logout</span>
            </button>
        </form>
    </div>
</nav>

<div class="container py-3" style="max-width:1200px">

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

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="profile-card mb-3">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                             style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                    </div>
                    <div class="col">
                        <div style="font-size:1rem;font-weight:700;color:#1e293b">{{ $user->full_name }}</div>
                        <div style="font-size:0.75rem;color:#64748b">
                            <i class="bi bi-card-text me-1"></i>{{ $user->student_id }}
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-primary" style="font-size:0.65rem">{{ $user->department }}</span>
                            <span class="badge bg-secondary" style="font-size:0.65rem">Year {{ $user->year_level }}</span>
                            <span class="badge bg-info" style="font-size:0.65rem">{{ $user->section }}</span>
                        </div>
                    </div>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-around text-center">
                    <div>
                        <div class="stats-number text-primary">{{ $pendingSessions->count() }}</div>
                        <div class="stats-label">Pending</div>
                    </div>
                    <div>
                        <div class="stats-number text-success">{{ $votedActiveSessions->count() }}</div>
                        <div class="stats-label">Active</div>
                    </div>
                    <div>
                        <div class="stats-number text-info">{{ $completedVotedSessions->count() }}</div>
                        <div class="stats-label">Completed</div>
                    </div>
                    <div>
                        <div class="stats-number text-danger">{{ $missedSessions->count() }}</div>
                        <div class="stats-label">Missed</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3" style="border-radius:14px">
                <div class="card-body">
                    <h6 class="fw-bold mb-2" style="font-size:0.85rem"><i class="bi bi-graph-up me-2 text-primary"></i>Your Voting Activity</h6>
                    @php
                        $totalParticipated = $votedActiveSessions->count() + $completedVotedSessions->count();
                        $totalEligible = $pendingSessions->count() + $totalParticipated + $missedSessions->count();
                        $participationRate = $totalEligible > 0 ? round(($totalParticipated / $totalEligible) * 100) : 0;
                    @endphp
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Participation Rate</span>
                            <span class="fw-bold">{{ $participationRate }}%</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar bg-success" style="width: {{ $participationRate }}%; border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-4">
                            <div class="text-success fw-bold" style="font-size:0.9rem">{{ $totalParticipated }}</div>
                            <div class="small text-muted" style="font-size:0.7rem">Voted</div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning fw-bold" style="font-size:0.9rem">{{ $pendingSessions->count() }}</div>
                            <div class="small text-muted" style="font-size:0.7rem">Pending</div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger fw-bold" style="font-size:0.9rem">{{ $missedSessions->count() }}</div>
                            <div class="small text-muted" style="font-size:0.7rem">Missed</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius:14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <div class="card-body text-center">
                    <i class="bi bi-qr-code-scan fs-1 mb-2 d-block"></i>
                    <h6 class="fw-bold mb-1" style="font-size:0.9rem">Quick Access via QR</h6>
                    <p class="small opacity-75 mb-2" style="font-size:0.75rem">Scan, upload, or enter code to vote instantly</p>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#qrScannerModal" style="font-size:0.8rem">
                        <i class="bi bi-camera me-1"></i> Access Election
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Available Elections --}}
            <div class="section-title">
                <span class="dot dot-active"></span>
                Available Elections
                @if($pendingSessions->count())
                    <span class="badge bg-primary rounded-pill" style="font-size:0.7rem;">{{ $pendingSessions->count() }} pending</span>
                @endif
            </div>

            @forelse($pendingSessions as $session)
            <div class="election-card mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <div style="font-weight:700;color:#1e293b;font-size:1rem">{{ $session->title }}</div>
                                @if($session->requires_release_code)
                                    <span class="badge bg-warning text-dark" style="font-size:0.65rem">
                                        <i class="bi bi-qr-code me-1"></i>Code Required
                                    </span>
                                @endif
                                @if($session->end_date->diffInHours(now()) < 24 && $session->end_date > now())
                                    <span class="badge bg-danger text-white" style="font-size:0.65rem">
                                        <i class="bi bi-stopwatch me-1"></i>Ending soon
                                    </span>
                                @endif
                            </div>
                            @if($session->description)
                                <div style="font-size:0.8rem;color:#64748b;margin-bottom:0.5rem">{{ Str::limit($session->description, 80) }}</div>
                            @endif
                            <div class="d-flex gap-3 flex-wrap">
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ends {{ $session->end_date->format('M d, Y h:i A') }}
                                </div>
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-person-badge me-1"></i>{{ $session->positions->count() }} position(s)
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('student.ballot', $session) }}" class="btn-vote-now">
                                Vote Now <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state mb-3">
                <i class="bi bi-ballot"></i>
                <h6 class="mt-2" style="font-size:0.9rem">No elections available</h6>
                <p class="small mb-0">Check back later for new elections you can participate in.</p>
            </div>
            @endforelse

            {{-- Voted Active Sessions --}}
            @if($votedActiveSessions->count())
            <div class="section-title mt-3">
                <span class="dot dot-voted"></span>
                Live Election Results
                <span class="badge bg-danger" style="font-size:0.7rem">LIVE</span>
            </div>

            @foreach($votedActiveSessions as $session)
            <div class="election-card voted mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <div style="font-weight:600;color:#1e293b;font-size:0.95rem">{{ $session->title }}</div>
                                <span class="badge" style="background:#dcfce7;color:#166534;font-size:0.65rem;">
                                    <i class="bi bi-check2-circle me-1"></i>Voted
                                </span>
                                <span class="badge" style="background:#ef4444;color:white;font-size:0.65rem;">LIVE</span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap">
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ends {{ $session->end_date->format('M d, Y h:i A') }}
                                </div>
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 w-100 w-sm-auto">
                            <button class="btn-view-results" onclick="showLiveResults({{ $session->id }})">
                                <i class="bi bi-bar-chart-fill me-1"></i>Results
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
            <div class="section-title mt-3">
                <span class="dot dot-completed"></span>
                Completed Elections
                <span class="badge bg-secondary" style="font-size:0.7rem">Final Results</span>
            </div>

            @foreach($completedVotedSessions as $session)
            <div class="election-card completed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <div style="font-weight:600;color:#1e293b;font-size:0.95rem">{{ $session->title }}</div>
                                <span class="badge" style="background:#e9d5ff;color:#6b21a5;font-size:0.65rem;">
                                    <i class="bi bi-check2-circle me-1"></i>Voted
                                </span>
                                <span class="badge" style="background:#f3e8ff;color:#6b21a5;font-size:0.65rem;">
                                    <i class="bi bi-trophy me-1"></i>Completed
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap">
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                                </div>
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 w-100 w-sm-auto">
                            <button class="btn-view-results" onclick="showFinalResults({{ $session->id }})">
                                <i class="bi bi-trophy me-1"></i>Winners
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
            <div class="section-title mt-3">
                <span class="dot dot-missed"></span>
                Missed Elections
                <span class="badge bg-danger" style="font-size:0.7rem">You did not vote</span>
            </div>

            @foreach($missedSessions as $session)
            <div class="election-card missed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <div style="font-weight:600;color:#1e293b;font-size:0.95rem">{{ $session->title }}</div>
                                <span class="badge bg-secondary" style="font-size:0.65rem;">
                                    <i class="bi bi-x-circle me-1"></i>Missed
                                </span>
                                <span class="badge" style="background:#f3e8ff;color:#6b21a5;font-size:0.65rem;">
                                    <i class="bi bi-trophy me-1"></i>Completed
                                </span>
                            </div>
                            <div class="d-flex gap-3 flex-wrap">
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-calendar-x me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                                </div>
                                <div style="font-size:0.75rem;color:#94a3b8">
                                    <i class="bi bi-people me-1"></i>{{ $session->positions->count() }} positions
                                </div>
                            </div>
                        </div>
                        <div>
                            <button class="btn-view-results" onclick="showFinalResults({{ $session->id }})" style="background:#6c757d">
                                <i class="bi bi-eye me-1"></i>View Results
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top">
                        <div class="small text-muted d-flex align-items-center gap-2" style="font-size:0.7rem">
                            <i class="bi bi-info-circle"></i>
                            <span>You did not participate in this election.</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- QR Scanner Modal with Fallback Options --}}
<div class="modal fade qr-scanner-modal" id="qrScannerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:1rem">
                    <i class="bi bi-qr-code-scan me-2"></i>Access Election via QR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#scan-tab" type="button" role="tab">
                            <i class="bi bi-camera"></i> Scan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#upload-tab" type="button" role="tab">
                            <i class="bi bi-image"></i> Upload
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#code-tab" type="button" role="tab">
                            <i class="bi bi-keyboard"></i> Code
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Scan Tab -->
                    <div class="tab-pane fade show active" id="scan-tab" role="tabpanel">
                        <div id="qr-reader" style="width: 100%;"></div>
                        <div id="qr-reader-status" class="mt-2 small text-center text-muted"></div>
                        <div id="qr-result" class="mt-3" style="display: none;">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                QR Code detected! Redirecting...
                            </div>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div class="tab-pane fade" id="upload-tab" role="tabpanel">
                        <div class="text-center">
                            <i class="bi bi-qr-code fs-1 text-primary mb-3 d-block"></i>
                            <p class="small text-muted mb-3">Upload a QR code image from your device</p>
                            <input type="file" id="qr-upload" class="form-control" accept="image/*" style="max-width: 300px; margin: 0 auto;">
                            <div id="upload-result" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Manual Code Tab -->
                    <div class="tab-pane fade" id="code-tab" role="tabpanel">
                        <div class="text-center">
                            <i class="bi bi-key fs-1 text-primary mb-3 d-block"></i>
                            <p class="small text-muted mb-3">Enter the release code manually</p>
                            <div class="mb-3">
                                <input type="text" id="manual-code" class="form-control text-center"
                                       style="max-width: 250px; margin: 0 auto; font-size: 1rem; letter-spacing: 4px; font-family: monospace;"
                                       placeholder="XXXX-XXXX">
                            </div>
                            <button id="submit-manual-code" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top text-center">
                    <p class="text-muted small mb-0" style="font-size:0.7rem">
                        <i class="bi bi-info-circle"></i>
                        Scan, upload an image, or type the code manually
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Results Modal --}}
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content results-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white;">
                <h5 class="modal-title" style="font-size:1rem">
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
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content receipt-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a56db 0%, #1447c0 100%); color: white;">
                <h5 class="modal-title" style="font-size:1rem">
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
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="printReceipt()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;
    let isScanning = false;
    let stream = null;

    async function checkCameraSupport() {
        const statusDiv = document.getElementById('qr-reader-status');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Camera not supported. Please use Upload or Code tabs.
                    </div>
                `;
            }
            return false;
        }

        const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
        if (!isSecure) {
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Camera requires HTTPS. Please use Upload or Code tabs.
                    </div>
                `;
            }
            return false;
        }

        return true;
    }

    async function requestCameraPermission() {
        const statusDiv = document.getElementById('qr-reader-status');

        try {
            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> Requesting camera permission...';
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-check-circle"></i> Camera ready!';
            return true;
        } catch (err) {
            console.error('Camera permission denied:', err);
            let errorMessage = 'Camera access denied.';
            if (err.name === 'NotAllowedError') {
                errorMessage = 'Camera access was denied. Please allow camera access.';
            } else if (err.name === 'NotFoundError') {
                errorMessage = 'No camera found on this device.';
            }
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ${errorMessage}<br>
                        Please use Upload or Code tabs instead.
                    </div>
                `;
            }
            return false;
        }
    }

    async function startQrScanner() {
        if (isScanning) return;
        cameraSupported = await checkCameraSupport();
        if (!cameraSupported) return;
        const granted = await requestCameraPermission();
        if (!granted) return;
        const qrReader = document.getElementById('qr-reader');
        if (!qrReader) return;
        qrReader.innerHTML = '';
        try {
            html5QrCode = new Html5Qrcode("qr-reader", { verbose: false });
            const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
            await html5QrCode.start({ facingMode: "environment" }, qrConfig, onScanSuccess, onScanError);
            isScanning = true;
            const statusDiv = document.getElementById('qr-reader-status');
            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-camera-video"></i> Position QR code in frame.';
        } catch (err) {
            console.error('Unable to start scanner:', err);
        }
    }

    function stopQrScanner() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch((err) => console.error('Error stopping scanner:', err));
            html5QrCode = null;
            isScanning = false;
        }
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    async function onScanSuccess(decodedText) {
        console.log('QR Code detected:', decodedText);
        stopQrScanner();
        const qrResult = document.getElementById('qr-result');
        if (qrResult) {
            qrResult.style.display = 'block';
            qrResult.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> QR Code detected! Redirecting...</div>`;
        }
        setTimeout(() => {
            const modalElement = document.getElementById('qrScannerModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            window.location.href = `/vote/validate?code=${encodeURIComponent(decodedText)}`;
        }, 1500);
    }

    function onScanError(errorMessage) {}

    function setupUploadHandler() {
        const qrUpload = document.getElementById('qr-upload');
        if (qrUpload) {
            qrUpload.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const uploadResult = document.getElementById('upload-result');
                uploadResult.innerHTML = '<div class="text-primary"><i class="bi bi-hourglass-split"></i> Processing...</div>';
                const tempScanner = new Html5Qrcode("qr-reader");
                try {
                    const decodedText = await tempScanner.scanFile(file, false);
                    uploadResult.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> QR Code detected! Redirecting...</div>`;
                    setTimeout(() => {
                        const modalElement = document.getElementById('qrScannerModal');
                        if (modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) modal.hide();
                        }
                        window.location.href = `/vote/validate?code=${encodeURIComponent(decodedText)}`;
                    }, 1500);
                } catch (err) {
                    uploadResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i> No QR code found.</div>`;
                }
                qrUpload.value = '';
            });
        }
    }

    function setupManualCodeHandler() {
        const submitBtn = document.getElementById('submit-manual-code');
        const manualCodeInput = document.getElementById('manual-code');
        if (submitBtn && manualCodeInput) {
            submitBtn.addEventListener('click', () => {
                const code = manualCodeInput.value.trim().toUpperCase();
                if (!code) {
                    alert('Please enter a release code');
                    return;
                }
                const modalElement = document.getElementById('qrScannerModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
                window.location.href = `/vote/validate?code=${encodeURIComponent(code)}`;
            });
            manualCodeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') submitBtn.click();
            });
        }
    }

    const qrScannerModal = document.getElementById('qrScannerModal');
    if (qrScannerModal) {
        qrScannerModal.addEventListener('shown.bs.modal', function() {
            const scanTab = document.querySelector('[data-bs-target="#scan-tab"]');
            if (scanTab) {
                const tab = new bootstrap.Tab(scanTab);
                tab.show();
            }
            startQrScanner();
        });
        qrScannerModal.addEventListener('hidden.bs.modal', function() {
            stopQrScanner();
            const manualCode = document.getElementById('manual-code');
            if (manualCode) manualCode.value = '';
            const uploadResult = document.getElementById('upload-result');
            if (uploadResult) uploadResult.innerHTML = '';
            const qrResult = document.getElementById('qr-result');
            if (qrResult) qrResult.style.display = 'none';
        });
    }

    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('data-bs-target') === '#scan-tab') {
                startQrScanner();
            } else {
                stopQrScanner();
            }
        });
    });

    setupUploadHandler();
    setupManualCodeHandler();

    // Results and Receipt Functions
    async function showLiveResults(sessionId) {
        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        const modalTitle = document.getElementById('resultsModalTitle');
        const modalContent = document.getElementById('resultsModalContent');
        const lastUpdateSpan = document.getElementById('modalLastUpdate');

        modalTitle.innerHTML = '<i class="bi bi-bar-chart-fill me-2"></i>Loading Live Results...';
        modalContent.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Fetching results...</p></div>`;
        modal.show();

        try {
            const response = await fetch(`/results/${sessionId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                modalTitle.innerHTML = `<i class="bi bi-bar-chart-fill me-2"></i>${data.session_title} - Live Results`;
                lastUpdateSpan.innerHTML = `<i class="bi bi-clock me-1"></i>Updated: ${data.last_update}`;
                let resultsHtml = '<div class="results-container">';
                data.results.forEach(position => {
                    resultsHtml += `<div class="mb-4"><h5 class="fw-bold mb-3" style="font-size:0.95rem">${position.title}</h5><div class="list-group">`;
                    position.candidates.forEach(candidate => {
                        const percentage = position.total_votes > 0 ? (candidate.votes / position.total_votes * 100).toFixed(1) : 0;
                        resultsHtml += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                    <div><strong style="font-size:0.85rem">${candidate.name}</strong>${candidate.is_winner ? '<span class="badge bg-success ms-2" style="font-size:0.65rem"><i class="bi bi-trophy"></i> Leading</span>' : ''}</div>
                                    <div class="text-end"><span class="fw-bold" style="font-size:0.85rem">${candidate.votes}</span> <span class="text-muted" style="font-size:0.7rem">votes</span></div>
                                </div>
                                <div class="progress" style="height: 6px;"><div class="progress-bar bg-primary" style="width: ${percentage}%"></div></div>
                                <div class="small text-muted mt-1" style="font-size:0.7rem">${percentage}%</div>
                            </div>
                        `;
                    });
                    resultsHtml += `</div></div>`;
                });
                resultsHtml += '</div>';
                modalContent.innerHTML = resultsHtml;
            } else {
                modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.message || 'Unable to load results.'}</div>`;
            }
        } catch (error) {
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-wifi-off me-2"></i>Network error. Please try again.</div>`;
        }
    }

    async function showFinalResults(sessionId) {
        await showLiveResults(sessionId);
    }

    async function viewReceipt(sessionId) {
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        const receiptContent = document.getElementById('receiptContent');
        receiptContent.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading receipt...</p></div>`;
        modal.show();

        try {
            const response = await fetch(`/receipt/${sessionId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                let votesHtml = '';
                if (data.votes && data.votes.length > 0) {
                    data.votes.forEach(vote => {
                        votesHtml += `<div class="vote-item"><div class="vote-position">${vote.position_title}</div><div class="vote-candidate">${vote.candidate_name}</div></div>`;
                    });
                } else {
                    votesHtml = `<div class="abstain-item"><i class="bi bi-eye-slash me-2"></i><strong>You abstained from all positions</strong><div class="small mt-1">No votes were cast in this election.</div></div>`;
                }
                receiptContent.innerHTML = `<div><div class="receipt-id-box mb-3"><div class="receipt-id-label">Receipt ID</div><div class="receipt-id-value">${data.receipt_id}</div><div style="font-size:0.7rem;color:#94a3b8;margin-top:4px">${data.voted_at}</div></div><div><h6 style="font-size:0.75rem" class="mb-2">Your Votes</h6>${votesHtml}</div><div class="mt-3 text-center small text-muted" style="font-size:0.7rem"><i class="bi bi-shield-check"></i> Official voting receipt</div></div>`;
            } else {
                receiptContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.message || 'Receipt not found.'}</div>`;
            }
        } catch (error) {
            receiptContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-wifi-off me-2"></i>Network error. Please try again.</div>`;
        }
    }

    function printReceipt() {
        const receiptContent = document.getElementById('receiptContent').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html><head><title>Vote Receipt</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
            <body><div class="container mt-4">${receiptContent}</div><script>window.print();window.close();<\/script></body></html>
        `);
        printWindow.document.close();
    }
</script>
</body>
</html>
