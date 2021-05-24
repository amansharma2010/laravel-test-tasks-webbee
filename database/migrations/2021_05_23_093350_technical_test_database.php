<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TechnicalTestDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('events');
        
        Schema::create('categories', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('events', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
            $table->integer('for_how_many_days');
            $table->integer('slot_duration');
            $table->integer('preparation_time');
            $table->integer('booking_limit');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
        
        Schema::create('event_available_slots', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('event_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->string('active_day');
            $table->time('slot_start_time');
            $table->time('slot_end_time');
            $table->timestamps();
        });
        
        Schema::create('event_unavailable_slots', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('event_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->string('active_day');
            $table->time('slot_start_time');
            $table->time('slot_end_time');
            $table->timestamps();
        });
        
        Schema::create('bookings', function($table) {
            $table->increments('id');
            $table->integer('event_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->date('slot_date');
            $table->time('slot_time');
            $table->integer('slot_duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_available_slots');
        Schema::dropIfExists('event_unavailable_slots');
        Schema::dropIfExists('bookings');
    }
}
