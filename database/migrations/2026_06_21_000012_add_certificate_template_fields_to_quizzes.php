<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('certificate_template_title')
                ->default('Certificate of Achievement')
                ->after('pass_notification_cc_email');
            $table->text('certificate_template_body')
                ->nullable()
                ->after('certificate_template_title');
            $table->string('certificate_template_footer')
                ->nullable()
                ->after('certificate_template_body');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_template_title',
                'certificate_template_body',
                'certificate_template_footer',
            ]);
        });
    }
};
