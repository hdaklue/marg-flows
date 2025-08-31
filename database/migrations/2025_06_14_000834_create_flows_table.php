<?php

declare(strict_types=1);

use App\Enums\FlowStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->tinyInteger('stage')
                ->default(FlowStage::DRAFT->value);

            // $table->boolean('is_default')->default(false);

            // $table->smallInteger('order_column');

            $table->date('started_at')->nullable();
            // $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->date('canceled_at')->nullable();

            $table->json('settings')->nullable();

            // foreign keys
            $table->string('tenant_id');
            $table->string('creator_id');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'stage']);
            $table->index(['id', 'tenant_id']);
            $table->index(['id', 'creator_id']);
            $table->index(['id', 'creator_id', 'tenant_id']);
            // $table->index(['status', 'due_date']);
            // $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
