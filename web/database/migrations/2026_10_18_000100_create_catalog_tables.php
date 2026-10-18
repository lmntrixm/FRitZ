<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('sha256', 64)->nullable()->index();
            $table->string('kind', 16); // image, datasheet, project, logo
            $table->string('alt')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Latin, LTR
            $table->string('slug')->unique();
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('protocols', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_wireless')->default(false);
            // IP and Wi-Fi exist only as inactive rows: never in the public catalog, wizard or product form
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 32)->nullable();
            $table->string('art', 32)->nullable(); // placeholder drawing kind until real photos arrive
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('label');
            $table->string('type', 16); // select, multi, number, bool, text
            $table->string('unit', 32)->nullable();
            $table->decimal('num_min', 12, 3)->nullable();
            $table->decimal('num_max', 12, 3)->nullable();
            $table->decimal('num_step', 12, 3)->nullable();
            $table->boolean('is_filter')->default(true);   // in the public filter sheet
            $table->boolean('is_compare')->default(true);  // as a row in compare
            $table->boolean('in_finder')->default(false);  // in the wizard's small scoped filter
            $table->boolean('required_to_publish')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'key']);
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('category_attributes')->cascadeOnDelete();
            $table->string('value', 64);
            $table->string('label');
            $table->string('swatch', 16)->nullable(); // colour options only
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model_code')->unique(); // matching key for file updates
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('country_of_origin')->nullable();
            $table->text('description')->nullable();
            $table->string('pitch')->nullable(); // one-line reason shown in the product finder
            $table->string('status', 16)->default('draft'); // draft, published, archived
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            // «فقط داخلی، عمومی نمی‌شود» — admin only, hidden from every public model/serializer
            $table->decimal('internal_price', 15, 0)->nullable();
            $table->text('search_text')->nullable();
            $table->string('code_compact')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'category_id']);
        });

        Schema::create('product_protocol', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('protocol_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'protocol_id']);
        });

        Schema::create('product_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('category_attributes')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('attribute_options')->cascadeOnDelete();
            $table->decimal('num', 12, 3)->nullable();
            $table->boolean('bool')->nullable();
            $table->string('text')->nullable();
            $table->index(['attribute_id', 'option_id']);
            $table->index(['attribute_id', 'num']);
        });

        Schema::create('product_custom_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 16); // compatible, related
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['product_id', 'related_id', 'type']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16); // image, datasheet
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('hint')->nullable();
            $table->string('icon', 32)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('need_category', function (Blueprint $table) {
            $table->foreignId('need_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['need_id', 'category_id']);
        });

        Schema::create('showcase_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['showcase_projects', 'need_category', 'needs', 'product_media', 'product_relations',
            'product_custom_specs', 'product_values', 'product_protocol', 'products', 'attribute_options',
            'category_attributes', 'categories', 'protocols', 'brands', 'media'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
