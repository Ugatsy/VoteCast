<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Background blobs */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            pointer-events: none;
            animation: drift 12s ease-in-out infinite alternate;
            z-index: 0;
        }
        .blob1 {
            width: 500px; height: 500px;
            background: #1d4ed8;
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .blob2 {
            width: 400px; height: 400px;
            background: #7c3aed;
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        .blob3 {
            width: 300px; height: 300px;
            background: #0ea5e9;
            top: 40%; left: 50%;
            animation-delay: -8s;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.08); }
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .brand-text {
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: -1px;
            color: #fff;
            text-decoration: none;
        }
        .brand-text span { color: #3b82f6; }
        .nav-btn {
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .nav-btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            background: transparent;
        }
        .nav-btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        /* Login card */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            width: 100%;
            max-width: 420px;
            color: #fff;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .brand-login {
            font-size: 2.2rem;
            font-weight: 900;
            letter-spacing: -1px;
            color: #fff;
            line-height: 1;
        }
        .brand-login span { color: #3b82f6; }
        .portal-label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            margin-top: 0.4rem;
        }

        /* Form controls (dark) */
        .form-label-custom {
            color: rgba(255,255,255,0.6);
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control-dark {
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: #fff;
            padding: 0.9rem 1.25rem;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            outline: none;
            width: 100%;
        }
        .form-control-dark::placeholder { color: rgba(255,255,255,0.25); }
        .form-control-dark:focus {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.12);
            box-shadow: 0 0 0 4px rgba(59,130,246,0.2);
            color: #fff;
        }
        .form-control-dark.is-invalid {
            border-color: #f87171;
            background: rgba(248,113,113,0.08);
        }
        .invalid-feedback-custom {
            color: #fca5a5;
            font-size: 0.85rem;
            margin-top: 0.35rem;
        }

        .btn-login-custom {
            background: #3b82f6;
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            padding: 0.9rem;
            width: 100%;
            transition: background 0.2s, transform 0.15s;
            letter-spacing: 0.3px;
        }
        .btn-login-custom:hover { background: #2563eb; transform: translateY(-1px); color: #fff; }
        .btn-login-custom:active { transform: translateY(0); }

        .divider-dark {
            border-color: rgba(255,255,255,0.1);
        }
        .footer-link {
            color: rgba(255,255,255,0.3);
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link:hover { color: rgba(255,255,255,0.6); }

        .alert-glass {
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.4);
            border-radius: 10px;
            color: #86efac;
            font-size: 0.85rem;
            padding: 0.65rem 1rem;
        }
    </style>
</head>
<body>
    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <!-- Navbar -->
    <nav class="navbar-custom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <a href="{{ route('landing') }}" class="brand-text">
                    Vote<span>Cast</span>
                </a>
                <a href="{{ route('landing') }}" class="nav-btn nav-btn-outline">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </nav>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <div class="brand-login">Vote<span>Cast</span></div>
                <div class="portal-label">Admin Portal</div>
            </div>

            @if(session('success'))
                <div class="alert-glass mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label-custom">Email Address</label>
                    <input type="email" name="email"
                           class="form-control-dark @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" autofocus required>
                    @error('email')
                        <div class="invalid-feedback-custom">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label-custom">Password</label>
                    <input type="password" name="password"
                           class="form-control-dark @error('password') is-invalid @enderror"
                           required>
                    @error('password')
                        <div class="invalid-feedback-custom">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-login-custom">
                    <i class="bi bi-shield-lock me-1"></i> Sign In
                </button>
            </form>

            <hr class="divider-dark my-4">
            <p class="text-center mb-0">
                <a href="{{ route('student.landing') }}" class="footer-link">
                    <i class="bi bi-graduation-cap me-1"></i>Student Portal
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

