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
        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            // polymorph: inbound_mails / outbound_mails
            $table->morphs('mail');            // mail_type, mail_id

            $table->string('filename');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('disk');            // z. B. "emails", "s3"
            $table->string('path');            // storage-Pfad oder S3-Key
            $table->string('cid')->nullable(); // für Inline-Bilder
            $table->boolean('inline')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_attachments');
    }
};
