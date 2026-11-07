<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('account_number', 26);
            $table->timestamps();

            $table->unique(['user_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
