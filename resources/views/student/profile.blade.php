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
            border-radius: 50%;
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

        .candidacy-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .candidacy-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .candidacy-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

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

        .btn-submit-candidacy {
            background: #1a56db;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-submit-candidacy:hover {
            background: #1447c0;
            transform: translateY(-1px);
        }

        .manifesto-preview {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            min-width: 300px;
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
        <a href="{{ route('profile.index') }}" class="text-white text-decoration-none small active">
            <i class="bi bi-person-circle me-1"></i>My Profile
        </a>
        <span class="text-white small">
            <i class="bi bi-person-badge me-1"></i>{{ $user->full_name }}
        </span>
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
                    {!! $user->candidate_status_badge !!}
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
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Profile Info</h6>
                    <p class="small mb-2"><i class="bi bi-envelope me-2"></i>{{ $user->email }}</p>
                    <p class="small mb-2"><i class="bi bi-building me-2"></i>{{ $user->department }}</p>
                    <p class="small mb-0"><i class="bi bi-people me-2"></i>{{ $user->section }}</p>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Edit Profile Form --}}
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile</h5>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $user->full_name) }}" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section</label>
                        <input type="text" name="section" class="form-control @error('section') is-invalid @enderror"
                               value="{{ old('section', $user->section) }}">
                        @error('section')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </form>
            </div>

            {{-- Manifesto & Platform --}}
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-megaphone me-2 text-primary"></i>My Manifesto & Platform</h5>
                <form method="POST" action="{{ route('profile.manifesto') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Personal Manifesto</label>
                        <textarea name="manifesto" class="form-control @error('manifesto') is-invalid @enderror"
                                  rows="4" placeholder="Share your vision, values, and what you stand for...">{{ old('manifesto', $user->manifesto) }}</textarea>
                        <div class="form-text">This will be visible on your candidate profile when you apply.</div>
                        @error('manifesto')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Platform</label>
                        <textarea name="platform" class="form-control @error('platform') is-invalid @enderror"
                                  rows="6" placeholder="List your specific goals, plans, and commitments if elected...">{{ old('platform', $user->platform) }}</textarea>
                        <div class="form-text">Provide specific details about what you plan to accomplish.</div>
                        @error('platform')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Manifesto
                    </button>
                </form>
            </div>

            {{-- Apply for Candidacy --}}
            @if($activeSessions->isNotEmpty())
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-primary"></i>Apply for Candidacy</h5>
                <p class="small text-muted mb-3">Apply to become a candidate for available positions. Your application will be reviewed by election administrators.</p>

                @foreach($activeSessions as $session)
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">{{ $session->title }}</h6>
                        <div class="row g-2">
                            @foreach($session->positions as $position)
                                @php
                                    $existingCandidacy = $myCandidacies[$position->id] ?? null;
                                @endphp
                                <div class="col-md-6">
                                    <div class="candidacy-card p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <div class="fw-bold">{{ $position->title }}</div>
                                                <div class="small text-muted">Max winners: {{ $position->max_winners }}</div>
                                            </div>
                                            @if($existingCandidacy)
                                                <span class="candidacy-status status-{{ $existingCandidacy->is_approved ? 'approved' : 'pending' }}">
                                                    <i class="bi {{ $existingCandidacy->is_approved ? 'bi-check-circle' : 'bi-clock-history' }}"></i>
                                                    {{ $existingCandidacy->is_approved ? 'Approved' : 'Pending Review' }}
                                                </span>
                                            @endif
                                        </div>

                                        @if(!$existingCandidacy)
                                            <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal"
                                                    data-bs-target="#applyModal"
                                                    data-position-id="{{ $position->id }}"
                                                    data-position-title="{{ $position->title }}"
                                                    data-session-title="{{ $session->title }}">
                                                <i class="bi bi-send me-1"></i>Apply for this position
                                            </button>
                                        @elseif(!$existingCandidacy->is_approved)
                                            <form method="POST" action="{{ route('student.candidacy.withdraw', $existingCandidacy->id) }}" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Withdraw your application?')">
                                                    <i class="bi bi-x-circle me-1"></i>Withdraw Application
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- My Candidacies (Existing) --}}
            @if($myCandidacies->isNotEmpty())
            <div class="profile-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>My Candidacies</h5>
                @foreach($myCandidacies as $candidacy)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">{{ $candidacy->position->title }}</div>
                                <div class="small text-muted">{{ $candidacy->position->votingSession->title }}</div>
                                @if($candidacy->is_approved)
                                    <span class="badge bg-success mt-1">Approved Candidate</span>
                                @else
                                    <span class="badge bg-warning text-dark mt-1">Pending Approval</span>
                                @endif
                            </div>
                            @if($candidacy->manifesto)
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#manifesto-{{ $candidacy->id }}">
                                    <i class="bi bi-eye"></i> View Manifesto
                                </button>
                            @endif
                        </div>

                        @if($candidacy->manifesto)
                            <div class="collapse mt-2" id="manifesto-{{ $candidacy->id }}">
                                <div class="manifesto-preview">
                                    {!! nl2br(e($candidacy->manifesto)) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Apply for Candidacy Modal --}}
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.candidacy.apply') }}" id="candidacyForm">
                @csrf
                <div class="modal-header" style="background: linear-gradient(135deg, #1a56db 0%, #1447c0 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-send me-2"></i>Apply for Candidacy
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="position_id" id="modalPositionId">
                    <p>Apply for <strong id="modalPositionTitle"></strong> in <strong id="modalSessionTitle"></strong></p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Manifesto (Optional)</label>
                        <textarea name="manifesto" class="form-control" rows="5"
                                  placeholder="Why should students vote for you? What are your goals?"></textarea>
                        <div class="form-text">If left blank, your personal manifesto will be used.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="toast-notification"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Modal data binding
    const applyModal = document.getElementById('applyModal');
    if (applyModal) {
        applyModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const positionId = button.getAttribute('data-position-id');
            const positionTitle = button.getAttribute('data-position-title');
            const sessionTitle = button.getAttribute('data-session-title');

            document.getElementById('modalPositionId').value = positionId;
            document.getElementById('modalPositionTitle').textContent = positionTitle;
            document.getElementById('modalSessionTitle').textContent = sessionTitle;
        });
    }

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
