<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EnrollmentImport;
use App\Models\Enrollment;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index()
    {
        $batches     = UploadBatch::with('uploader')->latest()->get();
        $enrollments = Enrollment::current()->orderBy('course')->orderBy('section')->paginate(50);

        $currentSemester     = session('current_semester', '1st Semester');
        $currentAcademicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));

        return view('admin.enrollment.index', compact(
            'batches',
            'enrollments',
            'currentSemester',
            'currentAcademicYear'
        ));
    }

    public function upload(Request $request)
    {
        // Increase execution time limit to 5 minutes (300 seconds)
        set_time_limit(300);

        // Increase memory limit for large files
        ini_set('memory_limit', '512M');

        try {
            $request->validate([
                'excel_file'    => 'required|file|mimes:xlsx,xls|max:10240',
                'semester'      => 'required|string|max:50',
                'academic_year' => 'required|string|max:20',
            ]);

            $file     = $request->file('excel_file');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Store the file
            $path = $file->storeAs('uploads/enrollment', $filename, 'local');

            // Begin database transaction
            DB::beginTransaction();

            try {
                // Create batch record first so the importer can reference it
                $batch = UploadBatch::create([
                    'filename'       => $filename,
                    'semester'       => $request->semester,
                    'academic_year'  => $request->academic_year,
                    'total_records'  => 0,
                    'uploaded_by'    => auth()->id(),
                ]);

                // Run the importer
                $import = new EnrollmentImport($batch, $request->semester, $request->academic_year);
                Excel::import($import, $file);

                // Update total count
                $total = $import->getImported() + $import->getSkipped();
                $batch->update(['total_records' => $total]);

                // Commit transaction
                DB::commit();

                // Set active semester in session
                session([
                    'current_semester'      => $request->semester,
                    'current_academic_year' => $request->academic_year,
                ]);

                $msg = "Import complete! {$import->getImported()} students imported";
                if ($import->getSkipped() > 0) {
                    $msg .= ", {$import->getSkipped()} skipped";
                }

                if (count($import->getErrors()) > 0) {
                    $msg .= ". Errors: " . implode('; ', array_slice($import->getErrors(), 0, 5));
                    if (count($import->getErrors()) > 5) {
                        $msg .= "... and " . (count($import->getErrors()) - 5) . " more";
                    }
                }

                return redirect()->route('admin.enrollment.index')->with('success', $msg . '.');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Enrollment upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $request->file('excel_file') ? $request->file('excel_file')->getClientOriginalName() : null
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

        session([
            'current_semester'      => $request->semester,
            'current_academic_year' => $request->academic_year,
        ]);

        return back()->with('success', 'Active semester updated to ' . $request->semester . ' ' . $request->academic_year . '.');
    }
}
