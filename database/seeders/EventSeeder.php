<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EventAvailableSlot;
use App\Models\EventUnavailableSlot;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $weekdaysArray = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        
        DB::transaction(function($table) use ($weekdaysArray) {
            $category1 = Category::create([
                'name' => 'Category 1'
            ]);
            
            $event1 = Event::create([
                'name' => 'Event 1',
                'category_id' => $category1->id,
                'for_how_many_days' => 10,
                'slot_duration' => 20,
                'preparation_time' => 10,
                'booking_limit' => 2,
                'start_date' => '2021-05-23',
                'end_date' => '2021-06-01'
            ]);
            
            foreach ($weekdaysArray as $siDay) {
                $skipArray = array('tuesday', 'friday', 'sunday');
                
                if (!in_array($siDay, $skipArray)) {
                    EventAvailableSlot::create([
                        'name' => 'Available',
                        'slot_start_time' => '08:00',
                        'slot_end_time' => '20:00',
                        'active_day' => $siDay,
                        'event_id' => $event1->id
                    ]);
                }
                
                if (!in_array($siDay, $skipArray)) {
                    EventUnavailableSlot::create([
                        'name' => 'Lunch Break',
                        'slot_start_time' => '12:00',
                        'slot_end_time' => '13:00',
                        'active_day' => $siDay,
                        'event_id' => $event1->id
                    ]);
                }
            }
            
            $event2 = Event::create([
                'name' => 'Event 2',
                'category_id' => $category1->id,
                'for_how_many_days' => 12,
                'slot_duration' => 30,
                'preparation_time' => 0,
                'booking_limit' => 5,
                'start_date' => '2021-05-25',
                'end_date' => '2021-06-05'
            ]);
            
            foreach ($weekdaysArray as $siDay) {
                $skipArray = array('saturday', 'sunday');
                
                if (!in_array($siDay, $skipArray)) {
                    EventAvailableSlot::create([
                        'name' => 'Available',
                        'slot_start_time' => '08:00',
                        'slot_end_time' => '18:00',
                        'active_day' => $siDay,
                        'event_id' => $event2->id
                    ]);
                }
                
                if (!in_array($siDay, $skipArray)) {
                    EventUnavailableSlot::create([
                        'name' => 'Lunch Break',
                        'slot_start_time' => '12:00',
                        'slot_end_time' => '12:30',
                        'active_day' => $siDay,
                        'event_id' => $event2->id
                    ]);
                }
            }
            
            $event3 = Event::create([
                'name' => 'Event 3',
                'category_id' => $category1->id,
                'for_how_many_days' => 10,
                'slot_duration' => 20,
                'preparation_time' => 0,
                'booking_limit' => 0,
                'start_date' => '2021-05-26',
                'end_date' => '2021-06-04'
            ]);
            
            foreach ($weekdaysArray as $siDay) {
                $skipArray = array('sunday');
                
                if (!in_array($siDay, $skipArray)) {
                    EventAvailableSlot::create([
                        'name' => 'Available',
                        'slot_start_time' => '09:00',
                        'slot_end_time' => '19:00',
                        'active_day' => $siDay,
                        'event_id' => $event3->id
                    ]);
                }
                
                if (!in_array($siDay, $skipArray)) {
                    EventUnavailableSlot::create([
                        'name' => 'Lunch Break',
                        'slot_start_time' => '13:00',
                        'slot_end_time' => '14:00',
                        'active_day' => $siDay,
                        'event_id' => $event3->id
                    ]);
                }
            }
            
            $event4 = Event::create([
                'name' => 'Event 4',
                'category_id' => $category1->id,
                'for_how_many_days' => 2,
                'slot_duration' => 10,
                'preparation_time' => 30,
                'booking_limit' => 0,
                'start_date' => '2021-05-22',
                'end_date' => '2021-05-23'
            ]);
            
            foreach ($weekdaysArray as $siDay) {
                $skipArray = array('saturday', 'sunday');
                
                if (!in_array($siDay, $skipArray)) {
                    EventAvailableSlot::create([
                        'name' => 'Available',
                        'slot_start_time' => '09:00',
                        'slot_end_time' => '19:00',
                        'active_day' => $siDay,
                        'event_id' => $event4->id
                    ]);
                }
                
                if (!in_array($siDay, $skipArray)) {
                    EventUnavailableSlot::create([
                        'name' => 'Lunch Break',
                        'slot_start_time' => '12:00',
                        'slot_end_time' => '13:00',
                        'active_day' => $siDay,
                        'event_id' => $event4->id
                    ]);
                }
            }

        });
    }
}
