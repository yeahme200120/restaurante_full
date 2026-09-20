<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('status', 30)->default('activo');

            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'role_id', 'company_id'],
                'user_roles_user_role_company_unique'
            );

            $table->index(
                ['user_id', 'status'],
                'user_roles_user_status_index'
            );

            $table->index(
                ['role_id', 'status'],
                'user_roles_role_status_index'
            );

            $table->index(
                ['company_id', 'status'],
                'user_roles_company_status_index'
            );

            $table->index(
                ['user_id', 'company_id', 'status'],
                'user_roles_user_company_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};