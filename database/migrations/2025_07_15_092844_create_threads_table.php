<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_channel_email_threads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('email_account_id') // Bezug zum Account
                  ->constrained('comms_channel_email_accounts')
                  ->cascadeOnDelete();

            $table->string('token', 32)->unique();
            $table->string('subject')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_channel_email_threads');
    }
};