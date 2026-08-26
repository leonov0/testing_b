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
            $table->string('company_name');
            $table->string('company_address');
            $table->string('company_telephone', 40);
            $table->string('company_email');
            $table->string('owner_name');
            $table->string('owner_mobile', 40);
            $table->string('owner_email');
            $table->string('contact_name');
            $table->string('contact_mobile', 40);
            $table->string('contact_email');
            $table->boolean('deactivated')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // GTIN is 13 or 14 digits; stored as a string so leading zeros survive.
            $table->string('gtin', 14)->unique();
            $table->string('name_en');
            $table->string('name_fr');
            $table->text('description_en')->nullable();
            $table->text('description_fr')->nullable();
            $table->string('brand');
            $table->string('country_of_origin');
            $table->decimal('weight_gross', 10, 3);
            $table->decimal('weight_net', 10, 3);
            $table->string('weight_unit', 8);
            $table->string('image_path')->nullable();
            $table->boolean('is_hidden')->default(false)->index();
            $table->timestamps();

            $table->index('gtin', 'products_gtin_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('companies');
    }
};
