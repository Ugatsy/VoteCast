<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EnrollmentImport;
use App\Models\Enrollment;
use App\Models\UploadBatch;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index()
    {
        $batches = UploadBatch::with('uploader')->latest()->get();
        
        // Get current semester from database instead of session
        $currentSemesterModel = Semester::where('is_active', true)->first();
        
        if ($currentSemesterModel) {
            $currentSemester = $currentSemesterModel->name;
            $currentAcademicYear = $currentSemesterModel->academic_year ?? 
                date('Y') . '-' . (date('Y') + 1);
        } else {
            // Fallback to session or defaults
            $currentSemester = session('current_semester', '1st Semester');
            $currentAcademicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));
        }
        
        $enrollments = Enrollment::current()->orderBy('course')->orderBy('section')->paginate(50);
        
        // Sync session for backward compatibility
        session([
            'current_semester' => $currentSemester,
            'current_academic_year' => $currentAcademicYear,
        ]);
        
        return view('admin.enrollment.index', compact(
            'batches',
            'enrollments',
            'currentSemester',
            'currentAcademicYear'
        ));
    }

    public function show(UploadBatch $batch)
    {
        $batch->load(['enrollments', 'uploader']);

        $courseCounts = $batch->enrollments
            ->groupBy('course')
            ->map(fn($group) => $group->count())
            ->sortByDesc(fn($count) => $count)
            ->toArray();

        return view('admin.enrollment.show', compact('batch', 'courseCounts'));
    }

    public function destroy(UploadBatch $batch)
    {
        DB::beginTransaction();

        try {
            Enrollment::where('upload_batch_id', $batch->id)->delete();
            $batch->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Batch delete failed: ' . $e->getMessage());

            return redirect()
                ->route('admin.enrollment.index')
                ->with('error', 'Failed to delete batch: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.enrollment.index')
            ->with('success', 'Enrollment batch deleted successfully.');
    }

    public function upload(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $request->validate([
                'excel_file'    => 'required|file|mimes:xlsx,xls|max:10240',
                'semester'      => 'required|string|max:50',
                'academic_year' => 'required|string|max:20',
            ]);

            $file     = $request->file('excel_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads/enrollment', $filename, 'local');

            DB::beginTransaction();

            try {
                $batch = UploadBatch::create([
                    'filename'      => $filename,
                    'semester'      => $request->semester,
                    'academic_year' => $request->academic_year,
                    'total_records' => 0,
                    'uploaded_by'   => auth()->id(),
                ]);

                $import = new EnrollmentImport($batch, $request->semester, $request->academic_year);
                Excel::import($import, $file);

                $total = $import->getImported() + $import->getUpdated() + $import->getSkipped();
                $batch->update([
                    'total_records' => $total,
                    'courses'       => $import->getCourses(),
                ]);

                DB::commit();

                // Switch the active semester to whatever was parsed from Row 5
                session([
                    'current_semester'      => $import->getSemester(),
                    'current_academic_year' => $import->getAcademicYear(),
                ]);

                // Build a clear success message
                $parts = [];
                if ($import->getImported() > 0) {
                    $parts[] = "{$import->getImported()} new students imported";
                }
                if ($import->getUpdated() > 0) {
                    $parts[] = "{$import->getUpdated()} existing students updated to {$import->getSemester()} {$import->getAcademicYear()}";
                }
                if ($import->getSkipped() > 0) {
                    $parts[] = "{$import->getSkipped()} skipped (duplicates / errors)";
                }

                $msg = 'Import complete! ' . implode(', ', $parts ?: ['no records processed']) . '.';

                if (count($import->getErrors()) > 0) {
                    $snippet = implode('; ', array_slice($import->getErrors(), 0, 5));
                    if (count($import->getErrors()) > 5) {
                        $snippet .= '... and ' . (count($import->getErrors()) - 5) . ' more';
                    }
                    $msg .= ' Errors: ' . $snippet;
                }

                return redirect()->route('admin.enrollment.index')->with('success', $msg);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Enrollment upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file'  => $request->file('excel_file')?->getClientOriginalName(),
            ]);

            return redirect()->route('admin.enrollment.index')
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function setSemester(Request $request)
    {
        $request->validate([
            'semester'      => 'required|string|max:50',
            'academic_year' => 'required|string|max:20',
        ]);
        
        // Deactivate all existing semesters
        Semester::where('is_active', true)->update(['is_active' => false]);
        
        // Create or update the selected semester
        $semester = Semester::firstOrCreate(
            [
                'name' => $request->semester,
                'academic_year' => $request->academic_year
            ],
            [
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
            ]
        );
        
        // If it already existed, just activate it
        if (!$semester->wasRecentlyCreated) {
            $semester->update(['is_active' => true]);
        }
        
        // Also update session for backward compatibility
        session([
            'current_semester' => $request->semester,
            'current_academic_year' => $request->academic_year,
        ]);
        
        return back()->with('success', 'Active semester updated to ' . $request->semester . ' ' . $request->academic_year . '.');
    }
}