<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comms_channel_email_accounts', function (Blueprint $table) {
            // Wer hat das Konto erstellt
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index()->after('team_id');

            // Ownership Type: 'team' oder 'user'
            $table->enum('ownership_type', ['team', 'user'])->default('team')->after('user_id');

            // Unique constraint: Entweder team_id ODER user_id muss gesetzt sein
            $table->unique(['team_id', 'user_id'], 'comms_email_ownership_unique');
        });

        // FK-Constraints nur hinzufügen, wenn Tabellen existieren
        if (Schema::hasTable('users')) {
            Schema::table('comms_channel_email_accounts', function (Blueprint $table) {
                $table->foreign('created_by_user_id')
                      ->references('id')->on('users')
                      ->cascadeOnDelete();
            });
        }

        // Bestehende Datensätze mit created_by_user_id = user_id aktualisieren
        DB::statement('UPDATE comms_channel_email_accounts SET created_by_user_id = user_id WHERE created_by_user_id IS NULL');
        
        // Bestehende Datensätze mit ownership_type = 'team' aktualisieren (wenn user_id null ist)
        DB::statement('UPDATE comms_channel_email_accounts SET ownership_type = "team" WHERE user_id IS NULL');
        DB::statement('UPDATE comms_channel_email_accounts SET ownership_type = "user" WHERE user_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('comms_channel_email_accounts', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropUnique('comms_email_ownership_unique');
            $table->dropColumn(['created_by_user_id', 'ownership_type']);
        });
    }
};
