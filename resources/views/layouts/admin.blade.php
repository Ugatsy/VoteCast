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

        /* ══════════════════════════════════════
           TOAST NOTIFICATION SYSTEM
        ══════════════════════════════════════ */
        #toast-container {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            pointer-events: none;
            width: 340px;
        }

        .vc-toast {
            pointer-events: all;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            background: #fff;
            border-radius: 14px;
            padding: 0.9rem 1rem;
            box-shadow:
                0 4px 6px -1px rgba(0,0,0,0.07),
                0 10px 30px -5px rgba(0,0,0,0.12),
                0 0 0 1px rgba(0,0,0,0.04);
            /* Slide-in from right */
            transform: translateX(calc(100% + 1.5rem));
            opacity: 0;
            transition:
                transform 0.38s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.28s ease;
            will-change: transform, opacity;
            overflow: hidden;
            position: relative;
        }

        .vc-toast.toast-visible {
            transform: translateX(0);
            opacity: 1;
        }

        .vc-toast.toast-hiding {
            transform: translateX(calc(100% + 1.5rem));
            opacity: 0;
        }

        /* Icon bubble */
        .toast-icon {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-top: 1px;
        }

        /* Text area */
        .toast-body {
            flex: 1;
            min-width: 0;
        }
        .toast-title {
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.2px;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .toast-message {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.4;
            word-break: break-word;
        }

        /* Close button */
        .toast-close {
            flex-shrink: 0;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1;
            transition: color 0.15s;
            margin-top: 1px;
        }
        .toast-close:hover { color: #475569; }

        /* Progress bar */
        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 14px 14px;
            width: 100%;
            transform-origin: left;
            animation: toast-drain linear forwards;
        }
        @keyframes toast-drain {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        /* ── Variants ── */

        /* Success */
        .vc-toast--success .toast-icon {
            background: #dcfce7;
            color: #16a34a;
        }
        .vc-toast--success .toast-title { color: #15803d; }
        .vc-toast--success .toast-progress { background: #22c55e; }
        .vc-toast--success {
            border-left: 4px solid #22c55e;
        }

        /* Error */
        .vc-toast--error .toast-icon {
            background: #fee2e2;
            color: #dc2626;
        }
        .vc-toast--error .toast-title { color: #b91c1c; }
        .vc-toast--error .toast-progress { background: #ef4444; }
        .vc-toast--error {
            border-left: 4px solid #ef4444;
        }

        /* Warning */
        .vc-toast--warning .toast-icon {
            background: #fef9c3;
            color: #ca8a04;
        }
        .vc-toast--warning .toast-title { color: #a16207; }
        .vc-toast--warning .toast-progress { background: #eab308; }
        .vc-toast--warning {
            border-left: 4px solid #eab308;
        }

        /* Info */
        .vc-toast--info .toast-icon {
            background: #dbeafe;
            color: #2563eb;
        }
        .vc-toast--info .toast-title { color: #1d4ed8; }
        .vc-toast--info .toast-progress { background: #3b82f6; }
        .vc-toast--info {
            border-left: 4px solid #3b82f6;
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- ── Toast Container ── -->
<div id="toast-container" aria-live="polite" aria-atomic="false"></div>

<!-- ── Flash data passed to JS ── -->
<script>
    window._vcFlash = {
        success: @json(session('success')),
        error:   @json(session('error')),
        warning: @json(session('warning')),
        info:    @json(session('info')),
        formError: @json($errors->any() ? $errors->first() : null),
    };
</script>

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
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ════════════════════════════════════════
   VoteCast Toast System
════════════════════════════════════════ */
const VCToast = (() => {
    const DURATION = 5000; // ms before auto-dismiss
    const container = document.getElementById('toast-container');

    const ICONS = {
        success: 'bi-check-lg',
        error:   'bi-x-lg',
        warning: 'bi-exclamation-lg',
        info:    'bi-info-lg',
    };

    const TITLES = {
        success: 'Success',
        error:   'Error',
        warning: 'Warning',
        info:    'Info',
    };

    function show(type, message, duration = DURATION) {
        if (!message) return;

        const toast = document.createElement('div');
        toast.className = `vc-toast vc-toast--${type}`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="bi ${ICONS[type] || ICONS.info}"></i>
            </div>
            <div class="toast-body">
                <div class="toast-title">${TITLES[type] || 'Notice'}</div>
                <div class="toast-message">${escapeHtml(message)}</div>
            </div>
            <button class="toast-close" aria-label="Dismiss">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
        `;

        container.appendChild(toast);

        // Trigger slide-in on next frame
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.add('toast-visible');
            });
        });

        // Auto-dismiss timer
        let dismissTimer = setTimeout(() => dismiss(toast), duration);

        // Pause progress & timer on hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(dismissTimer);
            const bar = toast.querySelector('.toast-progress');
            if (bar) bar.style.animationPlayState = 'paused';
        });
        toast.addEventListener('mouseleave', () => {
            const bar = toast.querySelector('.toast-progress');
            if (bar) bar.style.animationPlayState = 'running';
            dismissTimer = setTimeout(() => dismiss(toast), 1500);
        });

        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            clearTimeout(dismissTimer);
            dismiss(toast);
        });
    }

    function dismiss(toast) {
        toast.classList.add('toast-hiding');
        toast.classList.remove('toast-visible');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    return { show };
})();

/* ── Fire flash messages on load ── */
document.addEventListener('DOMContentLoaded', () => {
    const f = window._vcFlash || {};
    // Stagger them slightly if multiple show at once
    let delay = 0;
    const fire = (type, msg) => {
        if (!msg) return;
        setTimeout(() => VCToast.show(type, msg), delay);
        delay += 120;
    };

    fire('success', f.success);
    fire('error',   f.error);
    fire('warning', f.warning);
    fire('info',    f.info);
    fire('error',   f.formError);
});

/* ── Expose globally so child views can trigger toasts too ── */
window.VCToast = VCToast;
</script>

@stack('scripts')
</body>
</html>