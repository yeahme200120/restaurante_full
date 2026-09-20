<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_modules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('module_id');

            $table->string('status', 30)->default('activo');

            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'module_id'],
                'company_modules_company_module_unique'
            );

            $table->index(
                ['company_id', 'status'],
                'company_modules_company_status_index'
            );

            $table->index(
                ['module_id', 'status'],
                'company_modules_module_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
    }
};