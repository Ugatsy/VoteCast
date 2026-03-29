<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteCast — Vote Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4ff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .receipt-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(26,86,219,0.1);
        }

        .receipt-header {
            background: linear-gradient(135deg, #1a56db 0%, #1447c0 100%);
            color: #fff;
            padding: 2rem;
            text-align: center;
        }

        .receipt-header .check-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }

        .receipt-header h4 {
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .receipt-header p {
            opacity: 0.8;
            margin: 0;
            font-size: 0.9rem;
        }

        .receipt-id-box {
            background: #f0f4ff;
            border: 2px dashed #93c5fd;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            text-align: center;
            margin: 1.5rem 1.5rem 0;
        }

        .receipt-id-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .receipt-id-value {
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            font-weight: 800;
            color: #1a56db;
            letter-spacing: 2px;
            margin-top: 0.25rem;
        }

        .votes-section {
            padding: 1.5rem;
        }

        .votes-section h6 {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }

        .vote-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .vote-item:last-child {
            border-bottom: none;
        }

        .vote-position {
            font-size: 0.82rem;
            color: #64748b;
        }

        .vote-candidate {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            text-align: right;
        }

        .action-buttons {
            padding: 0 1.5rem 1.5rem;
            display: flex;
            gap: 0.75rem;
        }

        .btn-print {
            flex: 1;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            border-radius: 10px;
            padding: 0.7rem;
            font-weight: 600;
            color: #475569;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-print:hover {
            border-color: #1a56db;
            color: #1a56db;
        }

        .btn-home {
            flex: 1;
            background: #1a56db;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.7rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-home:hover {
            background: #1447c0;
            color: #fff;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="check-icon">✅</div>
            <h4>Vote Receipt</h4>
            <p>Official voting record for<br><strong>{{ $votingSession->title }}</strong></p>
        </div>

        <div class="receipt-id-box">
            <div class="receipt-id-label">Receipt ID — keep this for your records</div>
            <div class="receipt-id-value">{{ $receiptId }}</div>
            <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px">
                {{ now()->format('F d, Y \a\t H:i') }}
            </div>
        </div>

        <div class="votes-section">
            <h6>Your Votes</h6>
            @foreach($votes as $vote)
            <div class="vote-item">
                <div class="vote-position">{{ $vote->position->title }}</div>
                <div class="vote-candidate">{{ $vote->candidate->student->full_name }}</div>
            </div>
            @endforeach
        </div>

        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print Receipt
            </button>
            <a href="{{ route('student.dashboard') }}" class="btn-home">
                <i class="bi bi-house me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
