@if(session('show_codes_modal') && session('generated_codes') && count(session('generated_codes')) > 0)

@php
    $generatedCodes  = session('generated_codes');
    $firstCode       = $generatedCodes[0] ?? null;
    $sessionTitle    = session('generated_codes_session_id')
        ? (\App\Models\VotingSession::find(session('generated_codes_session_id'))?->title ?? 'Election')
        : 'Election';
@endphp

<div id="qrCodesModal" class="modal fade show" style="display:block; background:rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;">

            {{-- Header --}}
            <div class="modal-header" style="background:linear-gradient(135deg,#1a56db 0%,#7c3aed 100%); color:white; border-radius:20px 20px 0 0;">
                <div>
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-qr-code me-2"></i>Release Codes &amp; QR Codes Generated
                    </h5>
                    <p class="small mb-0 mt-1 opacity-75">Share these codes with students to access the election</p>
                </div>
                <button type="button" class="btn-close btn-close-white" id="closeQrModal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body" style="max-height:70vh; overflow-y:auto;">

                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>{{ count($generatedCodes) }} release code(s) generated successfully!</strong><br>
                    These codes will expire on
                    <strong>
                        {{ $firstCode && $firstCode->expires_at
                            ? \Carbon\Carbon::parse($firstCode->expires_at)->format('F d, Y h:i A')
                            : 'the election end date' }}
                    </strong>
                </div>

                <div class="row g-4">
                    @foreach($generatedCodes as $code)
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
                            <div class="card-body text-center">

                                {{-- SVG QR (rendered server-side, no PHP image ext needed) --}}
                                <div class="mb-3 p-3 rounded d-flex justify-content-center"
                                     style="background:#f8fafc;"
                                     data-qr-code="{{ $code->code }}">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                                        ->size(180)
                                        ->errorCorrection('H')
                                        ->generate($code->code) !!}
                                </div>

                                {{-- Code + copy --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <code class="fw-bold fs-4 bg-light px-3 py-2 rounded"
                                              style="letter-spacing:2px;">{{ $code->code }}</code>
                                        <button class="btn btn-sm btn-outline-primary copy-code-btn"
                                                data-code="{{ $code->code }}" title="Copy code">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Action buttons --}}
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    {{-- Download PNG (client-side, html2canvas) --}}
                                    <button class="btn btn-sm btn-outline-success qr-download-btn"
                                            data-code="{{ $code->code }}"
                                            data-title="{{ $sessionTitle }}">
                                        <i class="bi bi-download"></i> Download PNG
                                    </button>

                                    {{-- Print single card --}}
                                    <button class="btn btn-sm btn-outline-primary qr-print-single-btn"
                                            data-code="{{ $code->code }}"
                                            data-title="{{ $sessionTitle }}">
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
                    <button type="button" class="btn btn-outline-secondary" id="closeQrModal2">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" id="downloadAllCodesBtn" class="btn btn-success">
                        <i class="bi bi-download"></i> Download All PNG
                    </button>
                    <button type="button" id="printAllCodesBtn" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print All
                    </button>
                </div>

            </div>{{-- /modal-body --}}
        </div>
    </div>
</div>

<style>
.modal-backdrop { display:none; }
.code-card { transition:transform .2s; }
.code-card:hover { transform:translateY(-5px); }
</style>

<script>
(function () {
    // ── helpers ────────────────────────────────────────────────────────────────

    /** Close the modal */
    function closeModal() {
        const m = document.getElementById('qrCodesModal');
        if (m) m.style.display = 'none';
    }
    document.getElementById('closeQrModal')?.addEventListener('click', closeModal);
    document.getElementById('closeQrModal2')?.addEventListener('click', closeModal);

    /** Copy-to-clipboard */
    document.querySelectorAll('.copy-code-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.code);
            const orig = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
            setTimeout(() => { this.innerHTML = orig; }, 2000);
        });
    });

    // ── QR card builder ────────────────────────────────────────────────────────
    /**
     * Creates an off-screen div containing a styled QR card.
     * Uses the live SVG already on the page — no server round-trip.
     * Returns the element (caller must remove it after use).
     */
    function buildCardElement(code, title) {
        const wrap = document.createElement('div');
        wrap.style.cssText = [
            'position:fixed', 'left:-9999px', 'top:0',
            'width:360px', 'padding:24px 20px 20px',
            'background:#ffffff', 'border-radius:20px',
            'border:2px solid #1a56db',
            'font-family:Arial,sans-serif', 'text-align:center',
            'box-sizing:border-box'
        ].join(';');

        // gradient header bar
        const hdr = document.createElement('div');
        hdr.style.cssText = 'background:linear-gradient(135deg,#1a56db,#7c3aed);color:#fff;padding:9px 14px;border-radius:10px;margin-bottom:14px;font-weight:700;font-size:12px;letter-spacing:.4px;';
        hdr.textContent = 'VoteCast — Election Access';
        wrap.appendChild(hdr);

        // election title
        const ttl = document.createElement('div');
        ttl.style.cssText = 'font-size:13px;color:#374151;font-weight:600;margin-bottom:12px;line-height:1.4;';
        ttl.textContent = title;
        wrap.appendChild(ttl);

        // QR — clone the live SVG from the page
        const qrHolder = document.createElement('div');
        qrHolder.style.cssText = 'display:flex;justify-content:center;align-items:center;margin-bottom:12px;';
        const srcSvg = document.querySelector(`[data-qr-code="${code}"] svg`);
        if (srcSvg) {
            const clone = srcSvg.cloneNode(true);
            clone.setAttribute('width',  '190');
            clone.setAttribute('height', '190');
            clone.style.display = 'block';
            qrHolder.appendChild(clone);
        } else {
            qrHolder.innerHTML = '<div style="width:190px;height:190px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:11px;color:#999;">QR unavailable</div>';
        }
        wrap.appendChild(qrHolder);

        // code badge
        const badge = document.createElement('div');
        badge.style.cssText = 'font-size:26px;font-weight:800;letter-spacing:6px;font-family:monospace;background:#f0f4ff;padding:10px 18px;border-radius:10px;color:#1a56db;margin-bottom:10px;';
        badge.textContent = code;
        wrap.appendChild(badge);

        // hint
        const hint = document.createElement('div');
        hint.style.cssText = 'font-size:10px;color:#6b7280;';
        hint.textContent = 'Scan QR or enter code above to vote';
        wrap.appendChild(hint);

        document.body.appendChild(wrap);
        return wrap;
    }

    /** Download a single card as PNG via html2canvas */
    async function downloadCardAsPng(code, title, btnEl) {
        if (typeof html2canvas === 'undefined') {
            alert('html2canvas not loaded. Check your admin layout includes the CDN script.');
            return;
        }
        const origHtml = btnEl ? btnEl.innerHTML : '';
        if (btnEl) { btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; btnEl.disabled = true; }

        const card = buildCardElement(code, title);
        try {
            const canvas = await html2canvas(card, {
                scale: 3,                  // high-res → sharp PNG
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false
            });
            const link = document.createElement('a');
            link.download = `qrcode-${code}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (err) {
            console.error('html2canvas error:', err);
            alert('PNG generation failed: ' + err.message);
        } finally {
            document.body.removeChild(card);
            if (btnEl) { btnEl.innerHTML = origHtml; btnEl.disabled = false; }
        }
    }

    // ── per-card: Download PNG ─────────────────────────────────────────────────
    document.querySelectorAll('.qr-download-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            downloadCardAsPng(this.dataset.code, this.dataset.title, this);
        });
    });

    // ── per-card: Print (opens a clean print window with SVG inline) ───────────
    document.querySelectorAll('.qr-print-single-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const code  = this.dataset.code;
            const title = this.dataset.title;
            const srcSvg = document.querySelector(`[data-qr-code="${code}"] svg`);
            const svgHtml = srcSvg ? srcSvg.outerHTML : '';
            const pw = window.open('', '_blank');
            pw.document.write(`<!DOCTYPE html><html><head>
                <title>Release Code — ${code}</title>
                <style>
                    body{margin:0;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fff;font-family:Arial,sans-serif;}
                    .card{text-align:center;padding:30px 26px 24px;border:2px solid #1a56db;border-radius:20px;max-width:380px;width:100%;}
                    .hdr{background:linear-gradient(135deg,#1a56db,#7c3aed);color:#fff;padding:9px 14px;border-radius:10px;margin-bottom:14px;font-weight:700;font-size:13px;}
                    .ttl{font-size:14px;color:#374151;font-weight:600;margin-bottom:14px;line-height:1.4;}
                    .qr{display:flex;justify-content:center;margin-bottom:14px;}
                    .qr svg{width:200px;height:200px;}
                    .code{font-size:30px;font-weight:800;letter-spacing:6px;font-family:monospace;background:#f0f4ff;padding:12px 20px;border-radius:10px;color:#1a56db;margin-bottom:10px;}
                    .hint{font-size:11px;color:#6b7280;}
                </style>
            </head><body>
                <div class="card">
                    <div class="hdr">VoteCast — Election Access</div>
                    <div class="ttl">${title}</div>
                    <div class="qr">${svgHtml}</div>
                    <div class="code">${code}</div>
                    <div class="hint">Scan QR or enter code above to vote</div>
                </div>
                <script>window.onload=()=>{ window.print(); }<\/script>
            </body></html>`);
            pw.document.close();
        });
    });

    // ── Download All PNG (sequential, one file per code) ──────────────────────
    document.getElementById('downloadAllCodesBtn')?.addEventListener('click', async function () {
        const btns = document.querySelectorAll('.qr-download-btn');
        if (!btns.length) return;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Downloading…';
        this.disabled = true;
        for (const btn of btns) {
            await downloadCardAsPng(btn.dataset.code, btn.dataset.title, null);
            // small gap between downloads so browser doesn't block them
            await new Promise(r => setTimeout(r, 400));
        }
        this.innerHTML = '<i class="bi bi-download"></i> Download All PNG';
        this.disabled = false;
    });

    // ── Print All (single print window, grid layout, SVG inline) ──────────────
    document.getElementById('printAllCodesBtn')?.addEventListener('click', function () {
        const sessionTitle = document.querySelector('.qr-download-btn')?.dataset.title ?? 'Election';
        let cards = '';
        document.querySelectorAll('[data-qr-code]').forEach(el => {
            const code    = el.dataset.qrCode;
            const svgHtml = el.querySelector('svg')?.outerHTML ?? '';
            cards += `
                <div class="card">
                    <div class="hdr">VoteCast — Election Access</div>
                    <div class="ttl">${sessionTitle}</div>
                    <div class="qr">${svgHtml}</div>
                    <div class="code">${code}</div>
                    <div class="hint">Scan QR or enter code above to vote</div>
                </div>`;
        });

        const pw = window.open('', '_blank');
        pw.document.write(`<!DOCTYPE html><html><head>
            <title>All Release Codes — ${sessionTitle}</title>
            <style>
                body{font-family:Arial,sans-serif;padding:20px;background:#fff;}
                h1{text-align:center;color:#1a56db;margin-bottom:6px;}
                .sub{text-align:center;color:#6b7280;font-size:13px;margin-bottom:24px;}
                .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;}
                .card{text-align:center;padding:22px 18px 18px;border:2px solid #1a56db;border-radius:16px;page-break-inside:avoid;break-inside:avoid;}
                .hdr{background:linear-gradient(135deg,#1a56db,#7c3aed);color:#fff;padding:8px 12px;border-radius:8px;margin-bottom:12px;font-weight:700;font-size:12px;}
                .ttl{font-size:12px;color:#374151;font-weight:600;margin-bottom:10px;line-height:1.4;}
                .qr{display:flex;justify-content:center;margin-bottom:10px;}
                .qr svg{width:160px;height:160px;}
                .code{font-size:22px;font-weight:800;letter-spacing:4px;font-family:monospace;background:#f0f4ff;padding:9px 14px;border-radius:8px;color:#1a56db;margin-bottom:8px;}
                .hint{font-size:10px;color:#6b7280;}
                footer{text-align:center;margin-top:30px;font-size:11px;color:#9ca3af;}
                @media print{.card{page-break-inside:avoid;break-inside:avoid;}}
            </style>
        </head><body>
            <h1>Election Access Codes</h1>
            <p class="sub">${sessionTitle}</p>
            <div class="grid">${cards}</div>
            <footer>Generated by VoteCast Election System</footer>
            <script>window.onload=()=>{ window.print(); }<\/script>
        </body></html>`);
        pw.document.close();
    });

})();
</script>
@endif
