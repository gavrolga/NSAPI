<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->unique(); // защита от дублей
            $table->enum('channel', ['email', 'sms']);
            $table->text('message');
            $table->enum('priority', ['high', 'low'])->default('low');
            $table->enum('status', ['queued', 'sent', 'delivered', 'rejected'])
                ->default('queued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
