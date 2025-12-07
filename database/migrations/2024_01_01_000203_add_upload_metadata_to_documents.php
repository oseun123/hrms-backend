<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('file_url')->nullable()->after('file_path');
            $table->string('storage_driver', 20)->default('local')->after('mime_type');
            $table->string('cloudinary_public_id')->nullable()->after('storage_driver');
            $table->json('file_metadata')->nullable()->after('cloudinary_public_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'storage_driver', 'cloudinary_public_id', 'file_metadata']);
        });
    }
};
