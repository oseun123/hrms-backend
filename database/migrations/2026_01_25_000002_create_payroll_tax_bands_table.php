<?php

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
        Schema::create('payroll_tax_bands', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tax_scheme_id')->constrained('payroll_tax_schemes')->onDelete('cascade');
            $link->decimal('lower_limit', 15, 2);
            $link->decimal('upper_limit', 15, 2)->nullable();
            $link->decimal('rate_percentage', 5, 2);
            $link->decimal('flat_amount', 15, 2)->default(0);
            $link->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_bands');
    }
};
