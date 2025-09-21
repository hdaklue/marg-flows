<?php

declare(strict_types=1);

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
        Schema::connection(config('database.default'))->create('document_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('document_id'); // ULID reference to document
            $table->json('content'); // EditorJS content
            $table->ulid('created_by'); // ULID reference to user (not foreign key due to multi-db)
            $table->timestamp('created_at');

            $table->index(['document_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('database.default'))->dropIfExists('document_versions');
    }
};
