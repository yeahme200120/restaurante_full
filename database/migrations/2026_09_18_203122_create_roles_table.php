<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100);
            $table->string('name', 150);
            $table->string('description', 255)->nullable();

            $table->string('status', 30)->default('activo');

            $table->boolean('is_system')->default(false);

            $table->string('scope', 30)->default('company');

            $table->timestamps();

            $table->unique('code', 'roles_code_unique');

            $table->index(
                ['status', 'scope'],
                'roles_status_scope_index'
            );

            $table->index(
                ['is_system', 'status'],
                'roles_system_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};