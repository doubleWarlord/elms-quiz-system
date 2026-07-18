<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE question_media MODIFY media_type ENUM('text', 'paragraph', 'image', 'video', 'audio', 'document')");
        DB::statement("ALTER TABLE question_media MODIFY media_path VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE question_media MODIFY media_path VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE question_media MODIFY media_type ENUM('text', 'image', 'video', 'document')");
    }
};
