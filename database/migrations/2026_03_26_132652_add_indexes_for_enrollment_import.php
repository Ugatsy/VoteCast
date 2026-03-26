<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForEnrollmentImport extends Migration
{
    public function up()
    {
        // Add indexes to enrollments table for faster lookups
        Schema::table('enrollments', function (Blueprint $table) {
            // Composite index for checking existing enrollments
            $table->index(['student_code', 'semester', 'academic_year'], 'idx_student_semester_year');

            // Indexes for sorting and filtering
            $table->index('semester');
            $table->index('academic_year');
            $table->index('course');
            $table->index('section');
        });

        // Add index to users table for faster student_id lookups
        Schema::table('users', function (Blueprint $table) {
            $table->index('student_id', 'idx_student_id');
        });

        // Add index to upload_batches table
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->index('uploaded_by');
            $table->index(['semester', 'academic_year']);
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_student_semester_year');
            $table->dropIndex(['semester']);
            $table->dropIndex(['academic_year']);
            $table->dropIndex(['course']);
            $table->dropIndex(['section']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_student_id');
        });

        Schema::table('upload_batches', function (Blueprint $table) {
            $table->dropIndex(['uploaded_by']);
            $table->dropIndex(['semester', 'academic_year']);
        });
    }
}
