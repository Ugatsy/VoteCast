<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
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

        .btn-qr-scanner {
            background: #10b981; color: #fff; border: none;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-qr-scanner:hover { background: #059669; color: #fff; }

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
            padding: 0.5rem 1rem;
        }
        .nav-tabs .nav-link.active {
            color: #10b981;
            border-bottom: 2px solid #10b981;
            background: transparent;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #059669;
        }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <div class="d-flex gap-3 align-items-center">
        <button class="btn-qr-scanner" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
            <i class="bi bi-qr-code-scan"></i>
        </button>
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

            <div class="card border-0 shadow-sm" style="border-radius:14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <div class="card-body text-center">
                    <i class="bi bi-qr-code-scan fs-1 mb-2 d-block"></i>
                    <h6 class="fw-bold mb-2">Quick Access via QR</h6>
                    <p class="small opacity-75 mb-2">Scan, upload, or enter code to vote instantly</p>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
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
                    <span class="badge bg-primary rounded-pill" style="font-size:0.75rem;">{{ $pendingSessions->count() }} pending</span>
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
                                @if($session->requires_release_code)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-qr-code me-1"></i>Code Required
                                    </span>
                                @endif
                                @if($session->end_date->diffInHours(now()) < 24 && $session->end_date > now())
                                    <span class="badge bg-danger text-white">
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
                <span class="badge bg-danger">LIVE</span>
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
                                <span class="badge" style="background:#ef4444;color:white;">LIVE</span>
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
                <span class="badge bg-secondary">Final Results</span>
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
                <span class="badge bg-danger">You did not vote</span>
            </div>

            @foreach($missedSessions as $session)
            <div class="election-card missed mb-3">
                <div class="accent-bar" style="width: 100%"></div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                                <span class="badge bg-secondary">
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
                            <button class="btn btn-outline-secondary btn-sm" onclick="showFinalResults({{ $session->id }})">
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

{{-- QR Scanner Modal with Fallback Options --}}
<div class="modal fade qr-scanner-modal" id="qrScannerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-qr-code-scan me-2"></i>Access Election via QR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#scan-tab" type="button" role="tab">
                            <i class="bi bi-camera"></i> Scan QR
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#upload-tab" type="button" role="tab">
                            <i class="bi bi-image"></i> Upload Image
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#code-tab" type="button" role="tab">
                            <i class="bi bi-keyboard"></i> Enter Code
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
                                       style="max-width: 250px; margin: 0 auto; font-size: 1.2rem; letter-spacing: 4px; font-family: monospace;"
                                       placeholder="XXXX-XXXX">
                            </div>
                            <button id="submit-manual-code" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top text-center">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle"></i>
                        You can scan, upload an image, or type the code manually
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;
    let isScanning = false;
    let cameraSupported = false;
    let stream = null;

    // Comprehensive camera detection
    async function checkCameraSupport() {
        const statusDiv = document.getElementById('qr-reader-status');

        // Check if browser supports getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Camera not supported in this browser.</strong><br>
                        Please use one of these methods instead:
                        <ul class="mt-2 mb-0 text-start">
                            <li><i class="bi bi-image"></i> Upload a QR code image</li>
                            <li><i class="bi bi-keyboard"></i> Enter the code manually</li>
                        </ul>
                    </div>
                `;
            }
            return false;
        }

        // Check if we're on HTTPS or localhost
        const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
        if (!isSecure) {
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Camera requires HTTPS or localhost.</strong><br>
                        Current connection is not secure. Please use:
                        <ul class="mt-2 mb-0 text-start">
                            <li><i class="bi bi-image"></i> Upload a QR code image</li>
                            <li><i class="bi bi-keyboard"></i> Enter the code manually</li>
                        </ul>
                    </div>
                `;
            }
            return false;
        }

        return true;
    }

    // Request camera permission
    async function requestCameraPermission() {
        const statusDiv = document.getElementById('qr-reader-status');

        try {
            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> Requesting camera permission...';

            stream = await navigator.mediaDevices.getUserMedia({ video: true });

            // Stop the stream immediately, just checking permission
            stream.getTracks().forEach(track => track.stop());
            stream = null;

            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-check-circle"></i> Camera permission granted! Starting scanner...';
            return true;

        } catch (err) {
            console.error('Camera permission denied:', err);

            let errorMessage = '';
            if (err.name === 'NotAllowedError') {
                errorMessage = 'Camera access was denied. Please allow camera access in your browser settings.';
            } else if (err.name === 'NotFoundError') {
                errorMessage = 'No camera found on this device.';
            } else if (err.name === 'NotReadableError') {
                errorMessage = 'Camera is already in use by another application.';
            } else {
                errorMessage = err.message || 'Unable to access camera.';
            }

            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>${errorMessage}</strong><br>
                        Please use alternative methods:
                        <ul class="mt-2 mb-0 text-start">
                            <li><i class="bi bi-image"></i> Upload a QR code image</li>
                            <li><i class="bi bi-keyboard"></i> Enter the code manually</li>
                        </ul>
                        <div class="mt-2">
                            <button onclick="requestCameraPermission()" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-arrow-repeat me-1"></i> Try Again
                            </button>
                        </div>
                    </div>
                `;
            }
            return false;
        }
    }

    // Start QR scanner
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
            const qrConfig = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            await html5QrCode.start(
                { facingMode: "environment" },
                qrConfig,
                onScanSuccess,
                onScanError
            );

            isScanning = true;
            const statusDiv = document.getElementById('qr-reader-status');
            if (statusDiv) statusDiv.innerHTML = '<i class="bi bi-camera-video"></i> Camera ready. Position QR code in frame.';

        } catch (err) {
            console.error('Unable to start scanner:', err);
            const qrReader = document.getElementById('qr-reader');
            if (qrReader) {
                qrReader.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to start camera. Error: ${err.message || 'Unknown error'}<br><br>
                        <strong>Please use the "Upload Image" or "Enter Code" tabs instead.</strong>
                    </div>
                `;
            }
        }
    }

    function stopQrScanner() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                console.log('Scanner stopped');
            }).catch((err) => {
                console.error('Error stopping scanner:', err);
            });
            html5QrCode = null;
            isScanning = false;
        }

        // Also stop any active stream
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
            qrResult.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    QR Code detected! Redirecting...
                </div>
            `;
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

    function onScanError(errorMessage) {
        // Silent fail - just keep scanning
        // console.debug('Scan error:', errorMessage);
    }

    // Upload QR code image handler
    function setupUploadHandler() {
        const qrUpload = document.getElementById('qr-upload');
        if (qrUpload) {
            qrUpload.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const uploadResult = document.getElementById('upload-result');
                uploadResult.innerHTML = '<div class="text-primary"><i class="bi bi-hourglass-split"></i> Processing image...</div>';

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
                    uploadResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i> No QR code found in this image. Please try another image or enter the code manually.</div>`;
                }
                qrUpload.value = '';
            });
        }
    }

    // Manual code entry handler
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

    // Modal event listeners
    const qrScannerModal = document.getElementById('qrScannerModal');
    if (qrScannerModal) {
        qrScannerModal.addEventListener('shown.bs.modal', function() {
            // Reset to scan tab
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

    // Handle tab switching
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            if (e.target.getAttribute('data-bs-target') === '#scan-tab') {
                startQrScanner();
            } else {
                stopQrScanner();
            }
        });
    });

    // Initialize handlers
    setupUploadHandler();
    setupManualCodeHandler();
</script>
</body>
</html>
