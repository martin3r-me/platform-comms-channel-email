<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_channel_email_attachments', function (Blueprint $table) {
            $table->id();

            // Polymorph: inbound oder outbound
            $table->string('mail_type'); // z. B. CommsChannelEmailInboundMail
            $table->unsignedBigInteger('mail_id');

            $table->string('filename');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('disk');       // z. B. "emails", "s3"
            $table->string('path');       // z. B. storage-Pfad oder S3-Key
            $table->string('cid')->nullable();   // für Inline-Bilder
            $table->boolean('inline')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['mail_type', 'mail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_channel_email_attachments');
    }
};
