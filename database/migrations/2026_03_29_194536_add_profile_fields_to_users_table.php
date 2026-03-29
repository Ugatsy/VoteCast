<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('manifesto')->nullable()->after('photo');
            $table->string('platform')->nullable()->after('manifesto');
            $table->string('photo_public_id')->nullable()->after('photo');
            $table->boolean('is_candidate')->default(false)->after('is_active');
            $table->timestamp('candidate_applied_at')->nullable()->after('is_candidate');
            $table->enum('candidate_status', ['pending', 'approved', 'rejected'])->default('pending')->after('candidate_applied_at');
            $table->text('admin_remarks')->nullable()->after('candidate_status');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'manifesto', 'platform', 'photo_public_id',
                'is_candidate', 'candidate_applied_at',
                'candidate_status', 'admin_remarks'
            ]);
        });
    }
};
