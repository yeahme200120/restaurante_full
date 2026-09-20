<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->string('module', 100)->nullable();
            $table->string('action', 150);

            $table->string('record_type', 150)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('result', 30)->default('success');
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            $table->string('event_id', 100)->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->string('request_id', 100)->nullable();

            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 150)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('role_id');

            $table->index('company_id');
            $table->index('branch_id');

            $table->index('module');
            $table->index('action');

            $table->index('record_type');
            $table->index('record_id');

            $table->index('result');

            $table->index('event_id');
            $table->index('request_id');

            $table->index('created_at');

            $table->unique(
                ['company_id', 'idempotency_key'],
                'audit_logs_company_id_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
