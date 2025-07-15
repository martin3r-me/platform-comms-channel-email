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
        // -------------------------------------------------
        // Haupt-Tabelle
        // -------------------------------------------------
        Schema::create('inbound_mails', function (Blueprint $table) {
            $table->id();

            // Thread-Bezug
            $table->foreignId('thread_id')
                  ->constrained('threads')
                  ->cascadeOnDelete();

            // optionale User/Team-Spalten (ohne FK vorerst)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();

            // polymorpher Fallback-Sender (falls neither user noch team)
            $table->nullableMorphs('sender');   // sender_type + sender_id

            // Kopfzeilen (BCC kommt bei Inbound nicht mit)
            $table->string('from');
            $table->text('to');
            $table->text('cc')->nullable();
            $table->string('reply_to')->nullable();

            // Postmark-Meta & Inhalt
            $table->string('postmark_id')->nullable()->index();
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();

            // Webhook-spezifisch
            $table->json('headers')->nullable();
            $table->json('attachments')->nullable();
            $table->decimal('spam_score', 5, 2)->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------
        // optionale FK-Constraints nachträglich ergänzen
        // -------------------------------------------------
        if (Schema::hasTable('users')) {
            Schema::table('inbound_mails', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            });
        }

        if (Schema::hasTable('teams')) {
            Schema::table('inbound_mails', function (Blueprint $table) {
                $table->foreign('team_id')
                      ->references('id')->on('teams')
                      ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_mails');
    }
};