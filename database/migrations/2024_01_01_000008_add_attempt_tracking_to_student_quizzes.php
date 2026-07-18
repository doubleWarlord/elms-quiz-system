<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_quizzes', function (Blueprint $table) {
            // Ensure foreign keys have dedicated indexes before dropping composite unique index.
            $table->index('student_id', 'student_quizzes_student_id_index');
            $table->index('quiz_id', 'student_quizzes_quiz_id_index');

            // Drop the old unique constraint
            $table->dropUnique('student_quizzes_student_id_quiz_id_unique');
            
            // Add attempt_number column if it doesn't exist
            if (!Schema::hasColumn('student_quizzes', 'attempt_number')) {
                $table->integer('attempt_number')->default(1)->after('quiz_id');
            }
            
            // Create new unique constraint including attempt_number
            $table->unique(['student_id', 'quiz_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::table('student_quizzes', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('student_quizzes_student_id_quiz_id_attempt_number_unique');
            
            // Restore the old unique constraint
            $table->unique(['student_id', 'quiz_id']);
            
            // Remove attempt_number if we added it
            if (Schema::hasColumn('student_quizzes', 'attempt_number')) {
                $table->dropColumn('attempt_number');
            }
        });
    }
};
