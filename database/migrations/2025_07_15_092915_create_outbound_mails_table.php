<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_channel_email_outbound_mails', function (Blueprint $table) {
            $table->id();

            // Thread-Bezug
            $table->foreignId('thread_id')
                  ->constrained('comms_channel_email_threads')
                  ->cascadeOnDelete();

            // Optionale User-/Team-Zuordnung
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();

            // polymorpher Fallback-Sender
            $table->nullableMorphs('sender');

            // Kopfzeilen & Postmark-Metadaten
            $table->string('postmark_id')->nullable()->index();
            $table->string('from');
            $table->text('to');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject');
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();

            // Bodies
            $table->longText('html_body');
            $table->longText('text_body')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('users')) {
            Schema::table('comms_channel_email_outbound_mails', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            });
        }

        if (Schema::hasTable('teams')) {
            Schema::table('comms_channel_email_outbound_mails', function (Blueprint $table) {
                $table->foreign('team_id')
                      ->references('id')->on('teams')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_channel_email_outbound_mails');
    }
};