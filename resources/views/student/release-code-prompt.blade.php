<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — Release Code Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a56db 0%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .code-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            max-width: 450px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .code-input {
            font-size: 1.2rem;
            letter-spacing: 4px;
            text-align: center;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code-card text-center">
            <div class="mb-3">
                <i class="bi bi-shield-lock fs-1 text-primary"></i>
            </div>
            <h4 class="fw-bold mb-2">Release Code Required</h4>
            <p class="text-muted small mb-4">
                This election requires a release code to access.
                <br>Please enter the code provided by your administrator.
            </p>

            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Election: <strong>{{ $votingSession->title }}</strong>
            </div>

            <form method="POST" action="{{ route('student.ballot.validate', $votingSession) }}">
                @csrf
                <div class="mb-3">
                    <input type="text"
                           name="release_code"
                           class="form-control code-input form-control-lg @error('release_code') is-invalid @enderror"
                           placeholder="XXXX-XXXX"
                           autocomplete="off"
                           required>
                    @error('release_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Verify & Continue
                </button>
            </form>

            <hr class="my-4">

            <a href="{{ route('student.dashboard') }}" class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
