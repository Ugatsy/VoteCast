<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast — Student Voting Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            overflow: hidden;
        }

        /* Animated background blobs */
        .bg-blob {
            position: fixed; border-radius: 50%; filter: blur(80px);
            opacity: 0.18; pointer-events: none; animation: drift 12s ease-in-out infinite alternate;
        }
        .blob1 { width: 500px; height: 500px; background: #1d4ed8; top: -100px; left: -100px; animation-delay: 0s; }
        .blob2 { width: 400px; height: 400px; background: #7c3aed; bottom: -80px; right: -80px; animation-delay: -4s; }
        .blob3 { width: 300px; height: 300px; background: #0ea5e9; top: 40%; left: 50%; animation-delay: -8s; }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.08); }
        }

        .portal-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem 3.5rem;
            width: 100%;
            max-width: 460px;
            color: #fff;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        .brand {
            font-size: 2.8rem; font-weight: 900;
            letter-spacing: -2px; line-height: 1;
        }
        .brand span { color: #3b82f6; }

        .subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            margin-top: 0.4rem;
            letter-spacing: 0.3px;
        }

        .id-input {
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 0.9rem 1.25rem;
            text-align: center;
            width: 100%;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }
        .id-input::placeholder { color: rgba(255,255,255,0.25); font-weight: 400; font-size: 1rem; letter-spacing: 0; }
        .id-input:focus {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.12);
            box-shadow: 0 0 0 4px rgba(59,130,246,0.2);
        }
        .id-input.is-invalid {
            border-color: #f87171;
            background: rgba(248,113,113,0.08);
        }

        .btn-vote {
            background: #3b82f6;
            border: none; border-radius: 12px;
            color: #fff; font-size: 1rem; font-weight: 700;
            padding: 0.9rem;
            width: 100%;
            transition: background 0.2s, transform 0.15s;
            letter-spacing: 0.3px;
        }
        .btn-vote:hover  { background: #2563eb; color: #fff; transform: translateY(-1px); }
        .btn-vote:active { transform: translateY(0); }

        .error-msg {
            background: rgba(248,113,113,0.15);
            border: 1px solid rgba(248,113,113,0.4);
            border-radius: 10px;
            color: #fca5a5;
            font-size: 0.85rem;
            padding: 0.65rem 1rem;
        }

        .divider { border-color: rgba(255,255,255,0.1); }
        .admin-link { color: rgba(255,255,255,0.3); font-size: 0.8rem; text-decoration: none; }
        .admin-link:hover { color: rgba(255,255,255,0.6); }

        .feature-pill {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; padding: 0.3rem 0.75rem;
            font-size: 0.78rem; color: rgba(255,255,255,0.5);
        }
        .feature-pill i { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <div class="portal-card">
        {{-- Brand --}}
        <div class="text-center mb-4">
            <div class="brand">Vote<span>Cast</span></div>
            <div class="subtitle">Campus Election Portal</div>
        </div>

        {{-- Feature pills --}}
        <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
            <span class="feature-pill"><i class="bi bi-shield-check"></i> Secure</span>
            <span class="feature-pill"><i class="bi bi-receipt"></i> Receipts</span>
            <span class="feature-pill"><i class="bi bi-lightning-charge"></i> Instant</span>
        </div>

        {{-- Error --}}
        @if($errors->any())
        <div class="error-msg mb-3">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('student.verify') }}">
            @csrf
            <div class="mb-3">
                <label style="color:rgba(255,255,255,0.6);font-size:0.82rem;font-weight:600;letter-spacing:0.5px;text-transform:uppercase" class="mb-2 d-block">
                    Student ID
                </label>
                <input type="text"
                       name="student_id"
                       class="id-input @error('student_id') is-invalid @enderror"
                       placeholder="e.g.  00-00000"
                       value="{{ old('student_id') }}"
                       autofocus
                       autocomplete="off"
                       required>
            </div>
            <button type="submit" class="btn-vote">
                Access My Ballot &nbsp;→
            </button>
        </form>

        <hr class="divider my-4">
        <p class="text-center mb-0">
            <a href="{{ route('admin.login') }}" class="admin-link">
                <i class="bi bi-lock me-1"></i>Admin Portal
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
