<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_media', function (Blueprint $table) {
            $table->string('player_layout')
                ->default('default')
                ->after('requires_completion');
            $table->string('player_caption')
                ->nullable()
                ->after('player_layout');
        });
    }

    public function down(): void
    {
        Schema::table('question_media', function (Blueprint $table) {
            $table->dropColumn([
                'player_layout',
                'player_caption',
            ]);
        });
    }
};