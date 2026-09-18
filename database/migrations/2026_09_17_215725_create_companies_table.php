<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('legal_name', 200)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status', 30)->default('pendiente');
            $table->string('timezone', 100)->default('America/Mexico_City');
            $table->string('locale', 10)->default('es');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('tax_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
