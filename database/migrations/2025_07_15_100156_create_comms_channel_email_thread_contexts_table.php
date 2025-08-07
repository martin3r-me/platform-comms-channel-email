<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_channel_email_thread_contexts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('comms_channel_email_thread_id');

            $table->foreign('comms_channel_email_thread_id', 'email_thread_ctx_fk')
                  ->references('id')
                  ->on('comms_channel_email_threads')
                  ->onDelete('cascade');

            // Polymorpher Kontext mit manuell benanntem Index
            $table->string('context_type');
            $table->unsignedBigInteger('context_id');
            $table->index(['context_type', 'context_id'], 'email_thread_ctx_type_id_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('comms_channel_email_thread_contexts', function (Blueprint $table) {
            $table->dropForeign('email_thread_ctx_fk');
            $table->dropIndex('email_thread_ctx_type_id_idx');
        });

        Schema::dropIfExists('comms_channel_email_thread_contexts');
    }
};