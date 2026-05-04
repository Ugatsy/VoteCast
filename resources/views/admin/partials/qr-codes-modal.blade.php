@if(session('show_codes_modal') && session('generated_codes') && count(session('generated_codes')) > 0)
<div id="qrCodesModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a56db 0%, #7c3aed 100%); color: white; border-radius: 20px 20px 0 0;">
                <div>
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-qr-code me-2"></i>Release Codes & QR Codes Generated
                    </h5>
                    <p class="small mb-0 mt-1 opacity-75">Share these codes with students to access the election</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                @php
                    $generatedCodes = session('generated_codes');
                    $firstCode = $generatedCodes[0] ?? null;
                @endphp

                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>{{ count($generatedCodes) }} release code(s) generated successfully!</strong>
                    <br>These codes will expire on <strong>{{ $firstCode && $firstCode->expires_at ? \Carbon\Carbon::parse($firstCode->expires_at)->format('F d, Y h:i A') : 'the election end date' }}</strong>
                </div>

                <div class="row g-4">
                    @foreach($generatedCodes as $index => $code)
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                            <div class="card-body text-center">
                                <!-- QR Code -->
                                <div class="mb-3 p-3 bg-white rounded" style="background: #f8fafc;">
                                    {!! QrCode::size(180)->errorCorrection('H')->generate($code->code) !!}
                                </div>

                                <!-- Code Display -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                        <code class="fw-bold fs-4 bg-light px-3 py-2 rounded" style="letter-spacing: 2px;">{{ $code->code }}</code>
                                        <button class="btn btn-sm btn-outline-primary copy-code-btn" data-code="{{ $code->code }}" title="Copy Code">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Download Buttons -->
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="{{ route('admin.release-codes.qr.download', $code) }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-download"></i> QR PNG
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary print-code-btn" data-code="{{ $code->code }}" data-qr="{{ base64_encode(QrCode::size(200)->errorCorrection('H')->generate($code->code)) }}">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>

                                @if($code->description)
                                <div class="mt-2 small text-muted">
                                    <i class="bi bi-tag"></i> {{ $code->description }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <hr class="my-4">

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>How to use:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Students can <strong>type the code</strong> manually on the voting page</li>
                        <li>Students can <strong>scan the QR code</strong> using their phone camera</li>
                        <li>You can <strong>print these QR codes</strong> and display them around campus</li>
                        <li>Each code works for <strong>all eligible students</strong> (not one-time use)</li>
                    </ul>
                </div>

                <div class="d-flex gap-3 justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" id="printAllCodesBtn" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print All QR Codes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-backdrop {
        display: none;
    }
    .code-card {
        transition: transform 0.2s;
    }
    .code-card:hover {
        transform: translateY(-5px);
    }
    @media print {
        .modal-header, .alert, .modal-footer, .btn, .print-hide {
            display: none !important;
        }
        .modal-dialog {
            margin: 0;
            padding: 0;
            max-width: 100%;
        }
        .modal-content {
            border: none;
            box-shadow: none;
        }
        .modal {
            position: static;
            display: block;
            background: white;
        }
        .col-md-4 {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Copy code functionality
        document.querySelectorAll('.copy-code-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.dataset.code;
                navigator.clipboard.writeText(code);
                const originalIcon = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                setTimeout(() => {
                    this.innerHTML = originalIcon;
                }, 2000);
            });
        });

        // Print single code
        document.querySelectorAll('.print-code-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.dataset.code;
                const qrBase64 = this.dataset.qr;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Release Code - ${code}</title>
                        <style>
                            body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
                            .qr-card { text-align: center; padding: 30px; border: 2px solid #1a56db; border-radius: 20px; max-width: 400px; margin: 0 auto; }
                            .qr-code { margin: 20px 0; }
                            .code { font-size: 32px; font-weight: bold; letter-spacing: 4px; font-family: monospace; background: #f0f4ff; padding: 15px; border-radius: 10px; }
                            .title { font-size: 24px; font-weight: bold; color: #1a56db; }
                            .footer { margin-top: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="qr-card">
                            <div class="title">VoteCast Election Access</div>
                            <div class="qr-code"><img src="data:image/png;base64,${qrBase64}" style="width: 200px; height: 200px;"></div>
                            <div class="code">${code}</div>
                            <div class="footer">Scan QR code or enter code to vote</div>
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            });
        });

        // Print all codes
        document.getElementById('printAllCodesBtn')?.addEventListener('click', function() {
            const printWindow = window.open('', '_blank');
            let html = `
                <html>
                <head>
                    <title>Release Codes - All QR Codes</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .header h1 { color: #1a56db; }
                        .qr-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
                        .qr-card { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 15px; page-break-inside: avoid; }
                        .qr-code { margin: 15px 0; }
                        .code { font-size: 24px; font-weight: bold; letter-spacing: 2px; font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 8px; }
                        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                        @media print {
                            .qr-card { page-break-inside: avoid; break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Election Access Codes</h1>
                        <p>${document.querySelector('.alert-success strong')?.innerText || ''}</p>
                    </div>
                    <div class="qr-grid">
            `;

            document.querySelectorAll('.col-md-4').forEach(card => {
                const code = card.querySelector('.copy-code-btn')?.dataset.code;
                const qrImage = card.querySelector('svg')?.outerHTML || '';
                html += `
                    <div class="qr-card">
                        <div class="qr-code">${qrImage}</div>
                        <div class="code">${code}</div>
                        <div class="small">Scan to vote or enter code manually</div>
                    </div>
                `;
            });

            html += `
                    </div>
                    <div class="footer">
                        Generated by VoteCast Election System
                    </div>
                </body>
                </html>
            `;

            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        });
    });
</script>
@endif
