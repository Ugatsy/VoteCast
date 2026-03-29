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
        .profile-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
        .profile-value { font-weight: 600; font-size: 0.95rem; color: #1e293b; }

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

        .btn-vote-now {
            background: #1a56db; color: #fff; border: none;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: all 0.2s;
            display: inline-block;
        }
        .btn-vote-now:hover { background: #1447c0; color: #fff; transform: translateY(-1px); }

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

        .receipt-id-box {
            background: #f0f4ff;
            border: 2px dashed #93c5fd;
            border-radius: 12px;
            padding: 1rem;
        }

        .receipt-id {
            font-family: 'Courier New', monospace;
            background: #f8fafc;
            padding: 0.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
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

        .badge-upcoming {
            background: #fef3c7;
            color: #d97706;
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
                        <div class="stats-label">Completed Votes</div>
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

            {{-- Already Voted --}}
            @if($votedSessions->count())
            <div class="section-title mt-4">
                <span class="dot dot-voted"></span>
                Already Voted
                <span class="badge bg-secondary rounded-pill">{{ $votedSessions->count() }}</span>
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
                            </div>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-calendar-check me-1"></i>Voted on
                                    @php
                                        $vote = $session->votes()->where('voter_id', $user->id)->first();
                                        $voteDate = $vote ? $vote->created_at : $session->updated_at;
                                    @endphp
                                    {{ $voteDate ? $voteDate->format('M d, Y h:i A') : 'Date not available' }}
                                </div>
                                <div style="font-size:0.78rem;color:#94a3b8">
                                    <i class="bi bi-receipt me-1"></i>Receipt available
                                </div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <button class="btn-view-receipt" onclick="viewReceipt({{ $session->id }})">
                                <i class="bi bi-receipt me-1"></i>View Receipt
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
    // View receipt function with better error handling
    async function viewReceipt(sessionId) {
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        const receiptContent = document.getElementById('receiptContent');

        // Show loading state
        receiptContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading receipt...</p>
            </div>
        `;

        modal.show();

        try {
            const response = await fetch(`/receipt/${sessionId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            const votedDate = new Date(data.voted_at);
            const formattedDate = votedDate.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            receiptContent.innerHTML = `
                <div class="receipt-id-box text-center mb-3">
                    <div class="small text-muted mb-1">Receipt ID</div>
                    <div class="receipt-id fw-bold" style="font-family: monospace; font-size: 1.1rem;">${escapeHtml(data.receipt_id)}</div>
                    <div class="small text-muted mt-1">${formattedDate}</div>
                </div>

                <h6 class="fw-bold mb-3">Your Votes for: ${escapeHtml(data.session_title)}</h6>

                ${data.votes.length === 0 ? `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No votes found for this election.
                    </div>
                ` : `
                    <div class="list-group list-group-flush mb-3">
                        ${data.votes.map((vote, index) => `
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <div class="fw-semibold">${escapeHtml(vote.position)}</div>
                                    <div class="small text-muted">${escapeHtml(vote.candidate)}</div>
                                    <div class="small text-muted">${escapeHtml(vote.candidate_section)}</div>
                                </div>
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            </div>
                        `).join('')}
                    </div>
                `}

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    This receipt confirms your vote was securely recorded on ${formattedDate}.
                    <br>
                    <small class="text-muted">Receipt ID: ${escapeHtml(data.receipt_id)}</small>
                </div>
            `;

        } catch (error) {
            console.error('Receipt error:', error);
            receiptContent.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                    <p class="mt-3 text-danger fw-bold">Failed to load receipt</p>
                    <p class="small text-muted">${escapeHtml(error.message)}</p>
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
