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
            $table->unsignedBigInteger('created_by_user_id')->index();

            // Optional: individueller Benutzer (für private Konten)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Ownership Type: 'team' oder 'user'
            $table->enum('ownership_type', ['team', 'user'])->default('team');

            $table->string('address')->unique();
            $table->string('name')->nullable();
            $table->string('inbound_token', 40)->unique()->nullable();

            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: Entweder team_id ODER user_id muss gesetzt sein
            $table->unique(['team_id', 'user_id'], 'comms_email_ownership_unique');
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
                $table->foreign('created_by_user_id')
                      ->references('id')->on('users')
                      ->cascadeOnDelete();
                
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