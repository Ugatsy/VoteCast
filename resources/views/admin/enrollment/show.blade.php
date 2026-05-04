@extends('layouts.admin')
@section('title', 'Batch Details')

@section('content')

{{-- Session Alerts --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
    <i class="bi bi-x-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Header with Back Button --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-folder me-2 text-primary"></i>Batch: {{ $batch->filename }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.enrollment.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to History
        </a>
        <form method="POST" action="{{ route('admin.enrollment.batches.destroy', $batch) }}"
              onsubmit="return confirm('Are you sure you want to delete this batch? All {{ $batch->imported_records }} enrollments will be removed permanently.')"
              class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Delete Batch
            </button>
        </form>
    </div>
</div>

{{-- Batch Statistics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-excel text-success display-6"></i>
                <h6 class="mt-2 text-muted">Filename</h6>
                <p class="mb-0 small text-truncate">{{ $batch->filename }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-calendar3 text-primary display-6"></i>
                <h6 class="mt-2 text-muted">Period</h6>
                <p class="mb-0">{{ $batch->semester }}<br><small class="text-muted">{{ $batch->academic_year }}</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-success display-6"></i>
                <h6 class="mt-2 text-muted">Imported</h6>
                <p class="mb-0 fw-bold fs-5">{{ $batch->imported_records }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-person-circle text-secondary display-6"></i>
                <h6 class="mt-2 text-muted">Uploaded By</h6>
                <p class="mb-0">{{ $batch->uploader->full_name ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle text-warning display-6"></i>
                <h6 class="mt-2 text-muted">Skipped</h6>
                <p class="mb-0 fw-bold fs-5">{{ $batch->skipped_records }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-people text-info display-6"></i>
                <h6 class="mt-2 text-muted">Total Records</h6>
                <p class="mb-0 fw-bold fs-5">{{ $batch->total_records }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
            <div class="card-body text-center">
                <i class="bi bi-book text-primary display-6"></i>
                <h6 class="mt-2 text-muted">Courses Included</h6>
                <div class="d-flex flex-wrap justify-content-center gap-1 mt-2">
                    @if($batch->courses && count($batch->courses) > 0)
                        @foreach($batch->courses as $course)
                            <span class="badge bg-info text-dark">{{ $course }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Course Breakdown --}}
@if(count($courseCounts) > 0)
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-pie-chart me-2 text-primary"></i>Enrollment by Course</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($courseCounts as $course => $count)
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                    <span class="fw-semibold">{{ $course }}</span>
                    <span class="badge bg-primary rounded-pill">{{ $count }} students</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Student List --}}
<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-people me-2"></i>Students in This Batch</strong>
        <span class="badge bg-primary">{{ $batch->enrollments->count() }} students</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Student Code</th>
                    <th>Full Name</th>
                    <th>Sex</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batch->enrollments as $index => $e)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><code class="text-primary">{{ $e->student_code }}</code></td>
                <td>{{ $e->full_name }}</td>
                <td>{{ $e->sex === 'F' ? '♀ F' : '♂ M' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $e->course }}</span></td>
                <td>Year {{ $e->year_level }}</td>
                <td>{{ $e->section }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No enrollments found in this batch.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Import Errors --}}
@if($batch->errors && count($batch->errors) > 0)
<div class="card border-0 shadow-sm mt-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-exclamation-circle me-2 text-warning"></i>Import Errors ({{ count($batch->errors) }})</strong>
    </div>
    <div class="card-body">
        <ul class="list-group">
            @foreach($batch->errors as $error)
            <li class="list-group-item list-group-item-warning small">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@endsection

