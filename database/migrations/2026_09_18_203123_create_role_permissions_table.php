<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->timestamps();

            $table->unique(
                ['role_id', 'permission_id'],
                'role_permissions_role_permission_unique'
            );

            $table->index(
                ['role_id'],
                'role_permissions_role_index'
            );

            $table->index(
                ['permission_id'],
                'role_permissions_permission_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};