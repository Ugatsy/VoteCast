@extends('layouts.admin')
@section('title', 'Manage Candidates — ' . $votingSession->title)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0 small">
            <a href="{{ route('admin.sessions.show', $votingSession) }}">← Back to election</a>
        </p>
    </div>
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-eye me-1"></i>View Election
    </a>
</div>

<div class="row g-4">

    {{-- Left: Add Position --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
            <div class="card-header bg-white py-3">
                <strong><i class="bi bi-plus-circle me-2 text-primary"></i>Add Position</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sessions.positions.add', $votingSession) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. President" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Max Winners</label>
                        <input type="number" name="max_winners" class="form-control" value="1" min="1" max="10">
                    </div>
                    <button class="btn btn-primary w-100">Add Position</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Positions & Candidates --}}
    <div class="col-lg-8">
        @forelse($votingSession->positions as $position)
        <div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <strong>{{ $position->title }}</strong>
                <div class="d-flex gap-2 align-items-center">
                    <span class="text-muted small">{{ $position->candidates->count() }} candidate(s)</span>
                    <form method="POST" action="{{ route('admin.positions.delete', $position) }}"
                          onsubmit="return confirm('Delete this position and all its candidates?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Existing candidates --}}
            @if($position->candidates->count())
            <div class="px-3 pt-3">
                <div class="row g-2 mb-3">
                    @foreach($position->candidates as $candidate)
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $candidate->photo_url }}"
                                     style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                                <div>
                                    <div class="small fw-medium">{{ $candidate->student->full_name }}</div>
                                    <div class="text-muted" style="font-size:0.75rem">{{ $candidate->student->section }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.candidates.delete', $candidate) }}"
                                  onsubmit="return confirm('Remove this candidate?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0 px-1">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Add candidate form --}}
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('admin.positions.candidates.add', $position) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Select Student</label>
                            <select name="student_id" class="form-select form-select-sm" required>
                                <option value="">— Search student —</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->full_name }} ({{ $student->student_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Photo</label>
                            <input type="file" name="photo" class="form-control form-control-sm"
                                   accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Manifesto</label>
                            <input type="text" name="manifesto" class="form-control form-control-sm"
                                   placeholder="Short platform">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-success btn-sm w-100">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm p-4 text-center text-muted" style="border-radius:10px">
            <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
            No positions yet. Add your first position on the left.
        </div>
        @endforelse
    </div>

</div>

<div class="mt-3">
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-primary">
        Done — View Election →
    </a>
</div>
@endsection

@push('scripts')
<script>
    // Make student dropdowns searchable
    document.querySelectorAll('select[name="student_id"]').forEach(sel => {
        sel.addEventListener('keydown', e => {
            const q = e.key.toLowerCase();
            Array.from(sel.options).forEach(opt => {
                opt.style.display = opt.text.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
</script>
@endpush
