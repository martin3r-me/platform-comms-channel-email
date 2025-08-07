<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comms_channel_email_account_user', function (Blueprint $table) {
            $table->id();

            // FK auf das geteilte Konto
            $table->unsignedBigInteger('account_id');

            // FK auf den Nutzer, dem Zugriff gewährt wird
            $table->unsignedBigInteger('user_id');

            // optional für spätere Nachvollziehbarkeit
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // Foreign Key auf Accounts (immer vorhanden)
            $table->foreign('account_id')
                  ->references('id')->on('comms_channel_email_accounts')
                  ->cascadeOnDelete();

            // Optionaler Foreign Key auf users
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->cascadeOnDelete();
            } else {
                $table->index('user_id');
            }

            // Eindeutigkeit absichern (ein Nutzer soll nicht mehrfach Zugriff auf das gleiche Konto erhalten)
            $table->unique(['account_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_channel_email_account_user');
    }
};