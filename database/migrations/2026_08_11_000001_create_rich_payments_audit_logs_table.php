<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_payment_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gateway_id')->nullable()->constrained('rich_payment_gateways')->nullOnDelete();
            $table->nullableMorphs('actor');
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['gateway_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_payment_audit_logs');
    }
};
