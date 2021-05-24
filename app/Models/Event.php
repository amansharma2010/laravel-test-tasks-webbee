<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public function category() {
        return $this->belongsTo(Category::class);
    }
    
    public function workshops() {
        return $this->hasMany(Workshop::class);
    }
    
    public function bookings() {
        return $this->hasMany(Booking::class);
    }
    
    public function availableSlots() {
        return $this->hasMany(EventAvailableSlot::class);
    }
    
    public function unavailableSlots() {
        return $this->hasMany(EventUnavailableSlot::class);
    }
}
