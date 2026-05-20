<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['queued', 'sent', 'delivered', 'rejected'])
                ->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0); // счётчик попыток
            $table->text('last_error')->nullable();              // последняя ошибка
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications')
                ->cascadeOnDelete();

            // Один получатель - одно уведомление
            $table->unique(['notification_id', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
