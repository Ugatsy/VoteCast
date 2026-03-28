<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast - Campus Election Platform</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background blobs - matching student portal */
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
            width: 500px;
            height: 500px;
            background: #1d4ed8;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        .blob2 {
            width: 400px;
            height: 400px;
            background: #7c3aed;
            bottom: -80px;
            right: -80px;
            animation-delay: -4s;
        }
        .blob3 {
            width: 300px;
            height: 300px;
            background: #0ea5e9;
            top: 40%;
            left: 50%;
            animation-delay: -8s;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.08); }
        }

        /* Navbar styling */
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
        .brand-text span {
            color: #3b82f6;
        }
        .nav-btn {
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
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
        .nav-btn-primary {
            background: #3b82f6;
            color: #fff;
        }
        .nav-btn-primary:hover {
            background: #2563eb;
            color: #fff;
        }

        /* Hero section */
        .hero-section {
            position: relative;
            z-index: 1;
            padding: 5rem 0;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 100px;
            padding: 0.5rem 1.2rem;
            color: #3b82f6;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1.1;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .hero-title span {
            color: #3b82f6;
        }
        .hero-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.6;
        }
        .btn-hero {
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            margin: 0.5rem;
        }
        .btn-hero-primary {
            background: #3b82f6;
            color: #fff;
            border: none;
        }
        .btn-hero-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            color: #fff;
        }
        .btn-hero-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }
        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
        }
        .trust-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
        .trust-badge i {
            color: #3b82f6;
            font-size: 1rem;
        }

        /* Feature cards */
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.8rem;
            transition: all 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .feature-icon {
            width: 55px;
            height: 55px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
        }
        .feature-icon i {
            font-size: 1.8rem;
            color: #3b82f6;
        }
        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.8rem;
        }
        .feature-text {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        /* Steps section */
        .step-card {
            text-align: center;
            position: relative;
        }
        .step-number {
            font-size: 4rem;
            font-weight: 900;
            color: rgba(59, 130, 246, 0.2);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .step-icon {
            width: 70px;
            height: 70px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .step-icon i {
            font-size: 2rem;
            color: #3b82f6;
        }
        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .step-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }

        /* Stats section */
        .stats-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            position: relative;
            z-index: 1;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            color: #3b82f6;
            margin-bottom: 0.3rem;
        }
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        /* CTA card */
        .cta-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 3rem;
        }

        /* Footer */
        .footer {
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
            color: rgba(255, 255, 255, 0.6);
        }
        .social-link {
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.2s;
            font-size: 1.2rem;
            margin: 0 0.5rem;
        }
        .social-link:hover {
            color: #3b82f6;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 1rem;
        }
        .section-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Background blobs -->
    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <!-- Navbar -->
    <nav class="navbar-custom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <a href="/" class="brand-text">
                    Vote<span>Cast</span>
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('student.landing') }}" class="nav-btn nav-btn-outline">
                        <i class="bi bi-graduation-cap me-1"></i> Student Portal
                    </a>
                    <a href="{{ route('admin.login') }}" class="nav-btn nav-btn-primary">
                        <i class="bi bi-lock me-1"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <div class="hero-badge">
                <i class="bi bi-check-circle-fill"></i>
                Campus Election Platform
            </div>
            <h1 class="hero-title">
                Elections made<br>
                <span>simple & secure</span>
            </h1>
            <p class="hero-text">
                VoteCast is a complete campus election management system — from student enrollment
                and candidate setup to real-time voting and instant results.
            </p>
            <div>
                <a href="{{ route('student.landing') }}" class="btn btn-hero btn-hero-primary">
                    Cast Your Vote <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <a href="{{ route('admin.login') }}" class="btn btn-hero btn-hero-outline">
                    Admin Dashboard <i class="bi bi-chevron-right ms-1"></i>
                </a>
            </div>
            <div class="d-flex justify-content-center gap-4 mt-5">
                <span class="trust-badge"><i class="bi bi-check-circle-fill"></i> One vote per student</span>
                <span class="trust-badge"><i class="bi bi-check-circle-fill"></i> Real-time results</span>
                <span class="trust-badge"><i class="bi bi-check-circle-fill"></i> Printable receipts</span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" style="background: rgba(0,0,0,0.2);">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title">Everything you need to run elections</h2>
                <p class="section-subtitle">A full-featured platform designed for campus student council elections.</p>
            </div>
            <div class="row g-4">
                @php
                    $features = [
                        ['icon' => 'bi-shield-check', 'title' => 'Secure & Tamper-Proof', 'desc' => 'Every vote is protected with strict validation rules. Students can only vote once per election session.'],
                        ['icon' => 'bi-lightning-charge', 'title' => 'Instant Results', 'desc' => 'Real-time vote tallying with live progress bars and automatic winner detection the moment polls close.'],
                        ['icon' => 'bi-receipt', 'title' => 'Vote Receipts', 'desc' => 'Students receive a printable confirmation receipt after casting their ballot for full transparency.'],
                        ['icon' => 'bi-file-excel', 'title' => 'Excel Enrollment', 'desc' => 'Bulk-import student rosters via Excel uploads. Manage semesters and enrollment history effortlessly.'],
                        ['icon' => 'bi-people', 'title' => 'Multi-Position Ballots', 'desc' => 'Support for multiple positions per election — President, VP, Secretary, and more — all on a single ballot.'],
                        ['icon' => 'bi-graph-up', 'title' => 'Analytics Dashboard', 'desc' => 'Track turnout rates, enrollment stats, and election activity from a centralized admin dashboard.'],
                    ];
                @endphp
                @foreach($features as $feature)
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi {{ $feature['icon'] }}"></i>
                        </div>
                        <h3 class="feature-title">{{ $feature['title'] }}</h3>
                        <p class="feature-text">{{ $feature['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title">How it works</h2>
                <p class="section-subtitle">From setup to results in four simple steps.</p>
            </div>
            <div class="row g-4">
                @php
                    $steps = [
                        ['number' => '01', 'icon' => 'bi-layers', 'title' => 'Create an Election', 'desc' => 'Admins set up election sessions with titles, dates, and eligible positions.'],
                        ['number' => '02', 'icon' => 'bi-trophy', 'title' => 'Add Candidates', 'desc' => 'Assign candidates to positions with their details, party lists, and photos.'],
                        ['number' => '03', 'icon' => 'bi-check-circle', 'title' => 'Students Vote', 'desc' => 'Enrolled students log in, review candidates, and cast their votes securely.'],
                        ['number' => '04', 'icon' => 'bi-bar-chart-line', 'title' => 'View Results', 'desc' => 'Results are tallied in real time and displayed with charts and winner badges.'],
                    ];
                @endphp
                @foreach($steps as $step)
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">{{ $step['number'] }}</div>
                        <div class="step-icon">
                            <i class="bi {{ $step['icon'] }}"></i>
                        </div>
                        <h4 class="step-title">{{ $step['title'] }}</h4>
                        <p class="step-text">{{ $step['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section py-5">
        <div class="container py-4">
            <div class="row text-center g-4">
                @php
                    $stats = [
                        ['value' => '100%', 'label' => 'Vote Integrity', 'icon' => 'bi-shield-check'],
                        ['value' => '<1s', 'label' => 'Result Speed', 'icon' => 'bi-lightning-charge'],
                        ['value' => '∞', 'label' => 'Elections Supported', 'icon' => 'bi-infinity'],
                        ['value' => '24/7', 'label' => 'Availability', 'icon' => 'bi-clock'],
                    ];
                @endphp
                @foreach($stats as $stat)
                <div class="col-md-3 col-6">
                    <i class="bi {{ $stat['icon'] }} text-primary fs-1"></i>
                    <div class="stat-number">{!! $stat['value'] !!}</div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="cta-card text-center">
                <i class="bi bi-clock-history text-primary fs-1 mb-3 d-block"></i>
                <h2 class="section-title" style="font-size: 2rem;">Ready to run your election?</h2>
                <p class="text-white-50 mb-4" style="font-size: 1.1rem;">
                    Set up your next campus election in minutes. Secure, transparent, and effortless.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('admin.login') }}" class="btn btn-hero btn-hero-primary">
                        Get Started <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="{{ route('student.landing') }}" class="btn btn-hero btn-hero-outline">
                        I'm a Student <i class="bi bi-graduation-cap ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                    <div class="brand-text" style="font-size: 1.5rem;">
                        Vote<span>Cast</span>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <p class="mb-0 small">© {{ date('Y') }} VoteCast — Campus Election Portal</p>
                    <p class="mb-0 small">Programmed by: Jameson Valera</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
