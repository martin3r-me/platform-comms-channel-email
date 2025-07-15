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
        // Tabelle anlegen
        // -------------------------------------------------
        Schema::create('outbound_mails', function (Blueprint $table) {
            $table->id();

            // Thread-Bezug
            $table->foreignId('thread_id')
                  ->constrained('threads')
                  ->cascadeOnDelete();

            // optionale User-/Team-Spalten (ohne FK vorerst)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();

            // polymorpher Fallback-Sender
            $table->nullableMorphs('sender');   // sender_type + sender_id

            // Kopfzeilen & Postmark-Meta
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

        // -------------------------------------------------
        // FK-Constraints nur hinzufügen, wenn Tabellen existieren
        // -------------------------------------------------
        if (Schema::hasTable('users')) {
            Schema::table('outbound_mails', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            });
        }

        if (Schema::hasTable('teams')) {
            Schema::table('outbound_mails', function (Blueprint $table) {
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
        Schema::dropIfExists('outbound_mails');
    }
};