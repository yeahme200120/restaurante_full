<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->boolean('is_default')->default(false);
            $table->string('status', 30)->default('activa');
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index('user_id');
            $table->index('branch_id');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_assignments');
    }
};
