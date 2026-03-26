<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast — Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            min-height: 100vh; display: flex; align-items: center;
            justify-content: center; font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff; border-radius: 16px; padding: 2.5rem 3rem;
            width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .brand { font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; color: #0f172a; }
        .brand span { color: #1a56db; }
        .form-control { border-radius: 8px; padding: 0.7rem 1rem; border-color: #e2e8f0; }
        .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 3px rgba(26,86,219,0.15); }
        .btn-login {
            background: #1a56db; border: none; border-radius: 8px;
            padding: 0.75rem; font-weight: 600; font-size: 1rem;
            color: #fff; transition: background 0.2s;
        }
        .btn-login:hover { background: #1447c0; color: #fff; }
        .divider { border-color: #e2e8f0; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="brand text-center mb-1">Vote<span>Cast</span></div>
    <p class="text-center text-muted mb-4" style="font-size:0.9rem">Admin Portal</p>

    @if(session('success'))
        <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email Address</label>
            <input type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" autofocus required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small">Password</label>
            <input type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-login w-100">
            <i class="bi bi-shield-lock me-1"></i> Sign In
        </button>
    </form>

    <hr class="divider my-4">
    <p class="text-center mb-0" style="font-size:0.82rem; color:#94a3b8">
        <a href="{{ route('student.landing') }}" style="color:#94a3b8">← Back to Student Portal</a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
