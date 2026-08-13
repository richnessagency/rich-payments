<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rich_payment_gateways')) {
            Schema::create('rich_payment_gateways', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name');
                $table->string('environment', 16)->default('test');
                $table->boolean('active')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('capabilities')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rich_payment_methods')) {
            Schema::create('rich_payment_methods', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('gateway_id')->constrained('rich_payment_gateways')->cascadeOnDelete();
                $table->string('code', 64);
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
        }

        if (! Schema::hasTable('rich_payment_credentials')) {
            Schema::create('rich_payment_credentials', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('gateway_id')->constrained('rich_payment_gateways')->cascadeOnDelete();
                $table->string('environment', 16)->default('test');
                $table->string('key_name', 64);
                $table->text('encrypted_value')->nullable();
                $table->string('masked_preview')->nullable();
                $table->timestamp('last_rotated_at')->nullable();
                $table->timestamps();

                $table->unique(['gateway_id', 'environment', 'key_name']);
            });
        }

        if (! Schema::hasTable('rich_payment_attempts')) {
            Schema::create('rich_payment_attempts', function (Blueprint $table): void {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->string('payable_type', 191)->nullable();
                $table->unsignedBigInteger('payable_id')->nullable();
                $table->unsignedBigInteger('amount_minor');
                $table->string('currency', 3)->default('EGP');
                $table->string('gateway_code', 64);
                $table->string('method_code', 64)->nullable();
                $table->string('status', 64)->default('initiated');
                $table->string('merchant_reference', 191)->nullable()->index();
                $table->string('external_reference', 191)->nullable()->index();
                $table->string('checkout_url', 2048)->nullable();
                $table->json('customer_snapshot')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['payable_type', 'payable_id']);
                $table->index(['gateway_code', 'status']);
            });
        }

        if (! Schema::hasTable('rich_payment_transactions')) {
            Schema::create('rich_payment_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('attempt_id')->nullable()->constrained('rich_payment_attempts')->nullOnDelete();
                $table->string('gateway_code', 64);
                $table->string('external_transaction_id', 191)->nullable()->index();
                $table->string('status', 64);
                $table->boolean('success')->default(false);
                $table->unsignedBigInteger('paid_amount_minor')->nullable();
                $table->string('currency', 3)->nullable();
                $table->json('raw_payload_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rich_payment_webhook_events')) {
            Schema::create('rich_payment_webhook_events', function (Blueprint $table): void {
                $table->id();
                $table->string('gateway_code', 64);
                $table->string('event_id', 191)->nullable();
                $table->string('payload_hash', 64);
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
