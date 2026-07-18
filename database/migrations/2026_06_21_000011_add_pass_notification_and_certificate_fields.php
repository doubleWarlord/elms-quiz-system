<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('pass_notification_enabled')->default(true)->after('is_published');
            $table->string('pass_notification_cc_email')->nullable()->after('pass_notification_enabled');
        });

        Schema::table('student_quizzes', function (Blueprint $table) {
            $table->string('certificate_code')->nullable()->after('passed');
            $table->timestamp('certificate_generated_at')->nullable()->after('certificate_code');
            $table->timestamp('pass_notification_sent_at')->nullable()->after('certificate_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_code',
                'certificate_generated_at',
                'pass_notification_sent_at',
            ]);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'pass_notification_enabled',
                'pass_notification_cc_email',
            ]);
        });
    }
};
