<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE question_media MODIFY media_type ENUM('text', 'paragraph', 'image', 'video', 'audio', 'document', 'slide')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE question_media MODIFY media_type ENUM('text', 'paragraph', 'image', 'video', 'audio', 'document')");
    }
};
