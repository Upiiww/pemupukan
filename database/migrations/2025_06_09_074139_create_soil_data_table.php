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
    Schema::create('soil_data', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('device_id');
    $table->decimal('soil_moisture',8, 2);
    $table->decimal('soil_temperature', 8, 2);
    $table->decimal('soil_ph', 8, 2);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soil_data');
    }
};
