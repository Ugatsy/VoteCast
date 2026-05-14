<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_receipt_id_unique');
            // Or if you know the exact constraint name:
            // $table->dropUnique(['receipt_id']);
        });
    }

    public function down()
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->unique('receipt_id');
        });
    }
};
