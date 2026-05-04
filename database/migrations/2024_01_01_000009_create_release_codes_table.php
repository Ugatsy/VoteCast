<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('release_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['voting_session_id', 'code']);
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('release_codes');
    }
};
