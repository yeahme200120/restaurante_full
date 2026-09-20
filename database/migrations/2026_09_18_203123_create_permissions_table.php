<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->string('code', 150);
            $table->string('name', 150);
            $table->string('description', 255)->nullable();

            $table->string('module', 100);
            $table->string('section', 100)->nullable();
            $table->string('action', 100);

            $table->string('status', 30)->default('activo');

            $table->timestamps();

            $table->unique(
                'code',
                'permissions_code_unique'
            );

            $table->index(
                ['module', 'status'],
                'permissions_module_status_index'
            );

            $table->index(
                ['section', 'status'],
                'permissions_section_status_index'
            );

            $table->index(
                ['action', 'status'],
                'permissions_action_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};