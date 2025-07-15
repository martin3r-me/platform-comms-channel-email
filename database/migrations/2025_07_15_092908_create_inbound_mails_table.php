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
        Schema::create('inbound_mails', function (Blueprint $table) {
            $table->id();
             // Beziehungen
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('sender');          // sender_type, sender_id

            // Kopfzeilen
            $table->string('from');
            $table->text('to');                        // mehrere Empfänger möglich
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('reply_to')->nullable();

            // Postmark & Inhalt
            $table->string('postmark_id')->nullable()->index();
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_mails');
    }
};
