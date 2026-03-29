<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', system-ui, sans-serif; }

        .topnav {
            background: #1a56db;
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .topnav .brand { color: #fff; font-size: 1.3rem; font-weight: 800; }
        .topnav .brand span { color: #93c5fd; }

        .profile-sidebar {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            text-align: center;
            position: sticky;
            top: 80px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            object-fit: cover;
            border: 4px solid #1a56db;
            margin-bottom: 1rem;
            background: #f0f4ff;
        }

        .profile-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .photo-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .photo-upload-area:hover {
            border-color: #1a56db;
            background: #eff6ff;
        }

        .photo-preview {
            position: relative;
            display: inline-block;
        }

        .remove-photo-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .remove-photo-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            min-width: 300px;
        }

        .info-row {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
    </style>
</head>
<body>

<nav class="topnav">
    <div class="brand">Vote<span>Cast</span></div>
    <div class="d-flex gap-3 align-items-center">
        <a href="{{ route('student.dashboard') }}" class="text-white text-decoration-none small">
            <i class="bi bi-house me-1"></i>Dashboard
        </a>
        <div class="d-flex align-items-center gap-2" style="opacity:0.92">
            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}"
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.4)">
            <span class="text-white small d-none d-md-inline">{{ $user->full_name }}</span>
        </div>
        <form method="POST" action="{{ route('student.logout') }}" class="m-0">
            @csrf
            <button class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</nav>

<div class="container py-4" style="max-width: 1200px">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Sidebar - Profile Photo & Info --}}
        <div class="col-lg-4">
            <div class="profile-sidebar">
                <div class="photo-preview">
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}" class="profile-avatar" id="profileAvatar">
                    @if($user->photo)
                    <button class="remove-photo-btn" id="removePhotoBtn">
                        <i class="bi bi-x"></i>
                    </button>
                    @endif
                </div>

                <h5 class="mb-1">{{ $user->full_name }}</h5>
                <p class="text-muted small mb-3">{{ $user->student_id }}</p>

                <div class="mb-3">
                    <span class="badge bg-primary">{{ $user->department }}</span>
                    <span class="badge bg-secondary">Year {{ $user->year_level }}</span>
                    <span class="badge bg-info">{{ $user->section }}</span>
                </div>

                @if($user->is_candidate)
                <div class="mb-3">
                    <span class="badge bg-warning text-dark">Candidate Status: {{ ucfirst($user->candidate_status) }}</span>
                </div>
                @endif

                {{-- Photo Upload Area --}}
                <div class="photo-upload-area mb-3" id="photoUploadArea">
                    <i class="bi bi-cloud-upload fs-2 text-primary"></i>
                    <p class="mb-0 small">Click or drag to upload photo</p>
                    <p class="text-muted small">JPEG, PNG up to 2MB</p>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/jpg" style="display: none">
                </div>

                <div id="uploadProgress" class="progress mt-2" style="height: 5px; display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>

                <hr>

                <div class="text-start">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Profile Information</h6>

                    {{-- <div class="info-row">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div> --}}

                    <div class="info-row">
                        <div class="info-label">Department / Course</div>
                        <div class="info-value">{{ $user->department }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Section</div>
                        <div class="info-value">{{ $user->section }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Year Level</div>
                        <div class="info-value">{{ $user->year_level }}</div>
                    </div>

                    @if($user->is_candidate)
                    <div class="info-row">
                        <div class="info-label">Candidate Since</div>
                        <div class="info-value">{{ $user->candidate_applied_at ? $user->candidate_applied_at->format('M d, Y') : 'N/A' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content - Only Manifesto & Platform --}}
        <div class="col-lg-8">
            {{-- Manifesto & Platform --}}
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-megaphone me-2 text-primary"></i>My Manifesto & Platform</h5>
                <p class="small text-muted mb-3">Share your vision, values, and goals with the student body. This will be visible on your candidate profile.</p>

                <form method="POST" action="{{ route('profile.manifesto') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Personal Manifesto</label>
                        <textarea name="manifesto" class="form-control @error('manifesto') is-invalid @enderror"
                                  rows="5" placeholder="Share your vision, values, and what you stand for...">{{ old('manifesto', $user->manifesto) }}</textarea>
                        <div class="form-text">This will be visible on your candidate profile when you apply for a position.</div>
                        @error('manifesto')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Platform</label>
                        <textarea name="platform" class="form-control @error('platform') is-invalid @enderror"
                                  rows="8" placeholder="List your specific goals, plans, and commitments if elected...">{{ old('platform', $user->platform) }}</textarea>
                        <div class="form-text">Provide specific details about what you plan to accomplish if elected.</div>
                        @error('platform')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Manifesto
                    </button>
                </form>
            </div>

            {{-- Preview Section - Shows how manifesto will appear --}}
            @if($user->manifesto || $user->platform)
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-eye me-2 text-primary"></i>Preview</h5>
                <p class="small text-muted mb-3">How your manifesto and platform will appear to voters.</p>

                @if($user->manifesto)
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Manifesto</div>
                    <div class="p-3 bg-light rounded" style="background: #f8fafc;">
                        {{ $user->manifesto }}
                    </div>
                </div>
                @endif

                @if($user->platform)
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Platform</div>
                    <div class="p-3 bg-light rounded" style="background: #f8fafc; white-space: pre-line;">
                        {{ $user->platform }}
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="toast-notification"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Photo upload functionality
    const photoUploadArea = document.getElementById('photoUploadArea');
    const photoInput = document.getElementById('photoInput');
    const profileAvatar = document.getElementById('profileAvatar');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = uploadProgress?.querySelector('.progress-bar');

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toastHtml = `
            <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0 show" role="alert" data-bs-autohide="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        toastContainer.innerHTML = toastHtml;
        const toastElement = toastContainer.querySelector('.toast');
        const bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
        bsToast.show();

        setTimeout(() => {
            if (toastContainer.innerHTML === toastHtml) {
                toastContainer.innerHTML = '';
            }
        }, 3500);
    }

    if (photoUploadArea) {
        photoUploadArea.addEventListener('click', () => photoInput.click());

        photoUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            photoUploadArea.style.borderColor = '#1a56db';
            photoUploadArea.style.background = '#eff6ff';
        });

        photoUploadArea.addEventListener('dragleave', () => {
            photoUploadArea.style.borderColor = '#cbd5e1';
            photoUploadArea.style.background = 'transparent';
        });

        photoUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            photoUploadArea.style.borderColor = '#cbd5e1';
            photoUploadArea.style.background = 'transparent';
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                uploadPhoto(file);
            }
        });
    }

    if (photoInput) {
        photoInput.addEventListener('change', (e) => {
            if (e.target.files[0]) {
                uploadPhoto(e.target.files[0]);
            }
        });
    }

    async function uploadPhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);

        uploadProgress.style.display = 'block';
        progressBar.style.width = '0%';

        try {
            const response = await fetch('{{ route("profile.photo") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const interval = setInterval(() => {
                if (progressBar.style.width === '100%') {
                    clearInterval(interval);
                } else {
                    progressBar.style.width = Math.min(100, parseInt(progressBar.style.width) + 20) + '%';
                }
            }, 100);

            const result = await response.json();
            clearInterval(interval);
            progressBar.style.width = '100%';

            if (result.success) {
                profileAvatar.src = result.photo_url + '?t=' + Date.now();
                if (!document.getElementById('removePhotoBtn')) {
                    const newRemoveBtn = document.createElement('button');
                    newRemoveBtn.className = 'remove-photo-btn';
                    newRemoveBtn.id = 'removePhotoBtn';
                    newRemoveBtn.innerHTML = '<i class="bi bi-x"></i>';
                    document.querySelector('.photo-preview').appendChild(newRemoveBtn);
                    newRemoveBtn.addEventListener('click', removePhoto);
                }
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Failed to upload photo. Please try again.', 'error');
        } finally {
            setTimeout(() => {
                uploadProgress.style.display = 'none';
                progressBar.style.width = '0%';
            }, 1000);
        }
    }

    async function removePhoto() {
        if (confirm('Remove your profile photo?')) {
            try {
                const response = await fetch('{{ route("profile.photo.remove") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const result = await response.json();
                if (result.success) {
                    profileAvatar.src = result.default_avatar;
                    const removeBtn = document.getElementById('removePhotoBtn');
                    if (removeBtn) removeBtn.remove();
                    showToast('Photo removed successfully', 'success');
                } else {
                    showToast(result.message || 'Failed to remove photo', 'error');
                }
            } catch (error) {
                showToast('Failed to remove photo', 'error');
            }
        }
    }

    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', removePhoto);
    }
</script>
</body>
</html>
