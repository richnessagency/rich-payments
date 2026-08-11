<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('environment')->default('test');
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('capabilities')->nullable();
            $table->timestamps();
        });

        Schema::create('rich_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gateway_id')->constrained('rich_payment_gateways')->cascadeOnDelete();
            $table->string('code');
            $table->string('display_name_ar');
            $table->string('display_name_en')->nullable();
            $table->text('integration_identifier')->nullable();
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('fees_config')->nullable();
            $table->timestamps();

            $table->unique(['gateway_id', 'code']);
            $table->index(['active', 'sort_order']);
        });

        Schema::create('rich_payment_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gateway_id')->constrained('rich_payment_gateways')->cascadeOnDelete();
            $table->string('environment')->default('test');
            $table->string('key_name');
            $table->text('encrypted_value')->nullable();
            $table->string('masked_preview')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway_id', 'environment', 'key_name']);
        });

        Schema::create('rich_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->nullableMorphs('payable');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('EGP');
            $table->string('gateway_code');
            $table->string('method_code')->nullable();
            $table->string('status')->default('initiated');
            $table->string('merchant_reference')->nullable()->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('checkout_url')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['gateway_code', 'status']);
        });

        Schema::create('rich_payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->nullable()->constrained('rich_payment_attempts')->nullOnDelete();
            $table->string('gateway_code');
            $table->string('external_transaction_id')->nullable()->index();
            $table->string('status');
            $table->boolean('success')->default(false);
            $table->unsignedBigInteger('paid_amount_minor')->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('raw_payload_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('rich_payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway_code');
            $table->string('event_id')->nullable();
            $table->string('payload_hash');
            $table->boolean('verified')->default(false);
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['gateway_code', 'payload_hash']);
            $table->index(['gateway_code', 'verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_payment_webhook_events');
        Schema::dropIfExists('rich_payment_transactions');
        Schema::dropIfExists('rich_payment_attempts');
        Schema::dropIfExists('rich_payment_credentials');
        Schema::dropIfExists('rich_payment_methods');
        Schema::dropIfExists('rich_payment_gateways');
    }
};
