<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable= ['first_name', 'last_name', 'email', 'slot_date', 'slot_time', 'event_id', 'slot_duration'];
}
