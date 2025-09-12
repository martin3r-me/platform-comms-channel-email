<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_channel_email_accounts', function (Blueprint $table) {
            $table->id();

            // Pflichtfeld: Team-Zugehörigkeit (FK später ergänzt)
            $table->unsignedBigInteger('team_id')->index();

            // Wer hat das Konto erstellt
            

            // Optional: individueller Benutzer (für private Konten)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            

            $table->string('address')->unique();
            $table->string('name')->nullable();
            $table->string('inbound_token', 40)->unique()->nullable();

            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            
        });

        // FK-Constraints nur hinzufügen, wenn Tabellen existieren
        if (Schema::hasTable('teams')) {
            Schema::table('comms_channel_email_accounts', function (Blueprint $table) {
                $table->foreign('team_id')
                      ->references('id')->on('teams')
                      ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('comms_channel_email_accounts', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_channel_email_accounts');
    }
};