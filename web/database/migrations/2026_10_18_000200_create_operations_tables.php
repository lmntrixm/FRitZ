<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The catalog logs the click on «ارسال استعلام در واتساپ», not the delivery.
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('project')->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->text('message');
            $table->string('page_url')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status', 16)->default('new'); // new, seen, done
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_snapshot');
            $table->string('code_snapshot');
            $table->unsignedInteger('qty')->default(1);
        });

        Schema::create('updates', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('kind', 8); // fritz, xlsx
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('preview'); // preview, applied, failed, rolled_back
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('modified')->default(0);
            $table->json('diff')->nullable();
            $table->string('backup_path')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('user_invites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('role', 16);
            $table->char('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'user_invites', 'activity_log', 'updates', 'inquiry_items', 'inquiries'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
