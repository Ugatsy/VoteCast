<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast Admin — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --vc-primary: #1a56db;
            --vc-dark:    #0f172a;
            --sidebar-w:  260px;
        }
        * { box-sizing: border-box; }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w); position: fixed; top: 0; left: 0;
            height: 100vh; background: var(--vc-dark);
            overflow-y: auto; z-index: 200; display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.4rem 1.5rem; font-size: 1.4rem; font-weight: 800;
            color: #fff; border-bottom: 1px solid rgba(255,255,255,0.08);
            letter-spacing: -0.5px;
        }
        .sidebar-brand span { color: var(--vc-primary); }
        .sidebar-brand small { font-weight: 400; font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        .sidebar nav { flex: 1; padding: 0.75rem 0; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.6); padding: 0.7rem 1.5rem;
            display: flex; align-items: center; gap: 0.75rem;
            font-size: 0.9rem; transition: all 0.15s; border-left: 3px solid transparent;
            text-decoration: none;
        }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .sidebar .nav-link.active {
            color: #fff; background: rgba(26,86,219,0.2);
            border-left-color: var(--vc-primary);
        }
        .sidebar .nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }
        .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); }

        /* ── Main ── */
        .main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; }
        .topbar {
            background: #fff; padding: 0.9rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .topbar h5 { margin: 0; font-weight: 600; color: #1e293b; }
        .topbar .meta { font-size: 0.85rem; color: #64748b; }
        .page-body { padding: 1.75rem 2rem; }

        /* ── Helpers ── */
        .stat-card { background: #fff; border-radius: 10px; padding: 1.25rem 1.5rem;
                     border: 1px solid #e2e8f0; transition: box-shadow 0.2s; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .stat-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
        .stat-value { font-size: 2rem; font-weight: 700; line-height: 1.1; margin-top: 0.25rem; }

        .badge-status-active    { background:#dcfce7; color:#166534; }
        .badge-status-scheduled { background:#fef9c3; color:#713f12; }
        .badge-status-completed { background:#f1f5f9; color:#475569; }
        .badge-status-paused    { background:#ffedd5; color:#9a3412; }
        .badge-status-cancelled { background:#fee2e2; color:#991b1b; }

        .alert { border-radius: 8px; }
    </style>
    @stack('styles')
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        Vote<span>Cast</span>&nbsp;<small>Admin</small>
    </div>
    <nav>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('admin.enrollment.index') }}"
           class="nav-link @if(request()->routeIs('admin.enrollment.*')) active @endif">
            <i class="bi bi-file-earmark-spreadsheet"></i> Enrollment
        </a>
        <a href="{{ route('admin.sessions.index') }}"
           class="nav-link @if(request()->routeIs('admin.sessions.*') || request()->routeIs('admin.positions.*') || request()->routeIs('admin.candidates.*')) active @endif">
            <i class="bi bi-ballot"></i> Elections
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="text-white-50 small mb-2">{{ auth()->user()->full_name }}</div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-secondary w-100" style="color:rgba(255,255,255,0.6);border-color:rgba(255,255,255,0.2)">
                <i class="bi bi-box-arrow-left"></i> Logout
            </button>
        </form>
    </div>
</aside>

<div class="main-wrap">
    <div class="topbar">
        <h5>@yield('title', 'Dashboard')</h5>
        <span class="meta"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->full_name }}</span>
    </div>

    <div class="page-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
