<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast — My Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', system-ui, sans-serif; }

        .topnav {
            background: #1a56db;
            padding: 0.9rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .topnav .brand { color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: -0.5px; }
        .topnav .brand span { color: #93c5fd; }

        .profile-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        .profile-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
        .profile-value { font-weight: 600; font-size: 0.95rem; color: #1e293b; }

        .election-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e8f0;
            transition: box-shadow 0.2s, transform 0.15s;
            overflow: hidden;
        }
        .election-card:hover { box-shadow: 0 6px 20px rgba(26,86,219,0.12); transform: translateY(-2px); }

        .election-card .accent-bar { height: 4px; background: #1a56db; }
        .election-card.voted .accent-bar { background: #22c55e; }

        .btn-vote-now {
            background: #1a56db; color: #fff; border: none;
            border-radius: 8px; padding: 0.55rem 1.25rem;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: background 0.2s;
            display: inline-block;
        }
        .btn-vote-now:hover { background: #1447c0; color: #fff; }

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
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .empty-state {
            text-align: center; padding: 2.5rem 1rem; color: #94a3b8;
            background: #fff; border-radius: 14px; border: 1px dashed #e2e8f0;
        }
        .empty-state i { font-size: 2.5rem; opacity: 0.3; display: block; margin-bottom: 0.75rem; }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <form method="POST" action="{{ route('student.logout') }}">
        @csrf
        <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </button>
    </form>
</nav>

<div class="container py-4" style="max-width:780px">

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

    {{-- Profile Card --}}
    <div class="profile-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div style="width:52px;height:52px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#1a56db;font-weight:700">
                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                </div>
            </div>
            <div class="col">
                <div style="font-size:1.05rem;font-weight:700;color:#1e293b">{{ $user->full_name }}</div>
                <div style="font-size:0.82rem;color:#64748b">{{ $user->student_id }}</div>
            </div>
            <div class="col-auto text-center">
                <div class="profile-label">Course</div>
                <div class="profile-value">{{ $user->department }}</div>
            </div>
            <div class="col-auto text-center">
                <div class="profile-label">Year</div>
                <div class="profile-value">{{ $user->year_level }}</div>
            </div>
            <div class="col-auto text-center">
                <div class="profile-label">Section</div>
                <div class="profile-value">{{ $user->section }}</div>
            </div>
        </div>
    </div>

    {{-- Available Elections --}}
    <div class="section-title">
        <span class="dot dot-active"></span>
        Available Elections
        @if($pendingSessions->count())
            <span class="badge bg-primary rounded-pill" style="font-size:0.75rem">{{ $pendingSessions->count() }}</span>
        @endif
    </div>

    @forelse($pendingSessions as $session)
    <div class="election-card mb-3">
        <div class="accent-bar"></div>
        <div class="d-flex justify-content-between align-items-center p-3">
            <div>
                <div style="font-weight:600;color:#1e293b;font-size:1rem">{{ $session->title }}</div>
                @if($session->description)
                    <div style="font-size:0.82rem;color:#64748b;margin-top:2px">{{ Str::limit($session->description, 80) }}</div>
                @endif
                <div style="font-size:0.78rem;color:#94a3b8;margin-top:4px">
                    <i class="bi bi-clock me-1"></i>Ends {{ $session->end_date->diffForHumans() }}
                    &nbsp;·&nbsp;{{ $session->positions->count() ?? 0 }} position(s)
                </div>
            </div>
            <a href="{{ route('student.ballot', $session) }}" class="btn-vote-now ms-3">
                Vote Now →
            </a>
        </div>
    </div>
    @empty
    <div class="empty-state mb-4">
        <i class="bi bi-ballot"></i>
        No elections available for you right now.
    </div>
    @endforelse

    {{-- Already Voted --}}
    @if($votedSessions->count())
    <div class="section-title mt-4">
        <span class="dot dot-voted"></span>
        Already Voted
    </div>

    @foreach($votedSessions as $session)
    <div class="election-card voted mb-3">
        <div class="accent-bar"></div>
        <div class="d-flex justify-content-between align-items-center p-3">
            <div>
                <div style="font-weight:600;color:#1e293b">{{ $session->title }}</div>
                <div style="font-size:0.78rem;color:#94a3b8;margin-top:4px">
                    <i class="bi bi-clock me-1"></i>Ended {{ $session->end_date->format('M d, Y') }}
                </div>
            </div>
            <span class="badge" style="background:#dcfce7;color:#166534;border-radius:8px;padding:0.4rem 0.85rem;font-size:0.8rem">
                <i class="bi bi-check2 me-1"></i>Voted
            </span>
        </div>
    </div>
    @endforeach
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
