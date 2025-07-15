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
        Schema::create('outbound_mails', function (Blueprint $table) {
            $table->id();
            // FK-Beziehungen
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->nullableMorphs('sender');           // sender_type, sender_id

            // Postmark / Mail-Inhalte
            $table->string('postmark_id')->nullable()->index();
            $table->string('from');
            $table->text('to');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('reply_to')->nullable();;
            $table->string('subject');
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_mails');
    }
};
