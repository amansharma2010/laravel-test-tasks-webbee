<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Date;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EventsController extends BaseController
{
    /** get all events with there avalible slots **/
    public function getEvents(Request $request) {
        $daysLimit = (int) $request->days_limit;
        if ($daysLimit <= 0) { $daysLimit = 30; }
        
        $start_date = date('Y-m-d', strtotime('+'.$daysLimit.' days'));
        $end_date = date('Y-m-d');
        $events = Cache::remember('events-'.$start_date, 600, function () use ($start_date, $end_date) {
            return Event::with('category')->with('bookings')->with('availableSlots')->with('unavailableSlots')
            ->where('start_date', '<=', $start_date)
            ->where('end_date', '>', $end_date)
            ->orderBy('start_date')
            ->get();
        });
        
        $events = $events->toArray();
        $eventsArray = array();
        if (!empty($events)) {
            foreach ($events as $singleEvent) {
                $eventsArray[] = $this->parseEvent($singleEvent);
            }
        }
        
        return $this->sendResponse($eventsArray, 'Events retrieved successfully.');
    }
    /** End **/
    
    /** get single event start **/
    public function getSingleEvent($id, Request $request) {
        $booking_date = ((isset($request->booking_date)) ? $request->booking_date : null);
        $event = Event::with('category')->with('bookings')->with('availableSlots')->with('unavailableSlots')->find($id);
        if (empty($event)) {
            return $this->sendError('Invalid Event.', ['Event not found.'], 404);  
        }
        $eventArray = $this->parseEvent($event->toArray(), $booking_date);
        return $this->sendResponse($eventArray, 'Event retrieved successfully.');
    }
    /** get single event end **/
    
    /** Schedule Event **/
    public function postScheduleEvent($id, Request $request) {
        $booking_date = ((isset($request->slot_date)) ? $request->slot_date : null);
        $event = Event::with('category')->with('bookings')->with('availableSlots')->with('unavailableSlots')->find($id);
        if (empty($event)) {
            return $this->sendError('Invalid Event.', ['Event not found.'], 404);  
        }
        $eventArray = $this->parseEvent($event->toArray(), $booking_date);
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'slot_date' => 'required|date_format:Y-m-d',
            'slot_time' => ['required', 'date_format:H:i', function ($attribute, $value, $fail) use ($request, $eventArray) {
                $slotDate = $request->slot_date;
                $slotTime = $value;
                $slotDay = strtolower(date('l', strtotime($slotDate)));
                $slotsArray = (isset($eventArray['available_slots'][$slotDay]) ? $eventArray['available_slots'][$slotDay]['slots'] : array());
                
                if(!in_array($slotTime, $slotsArray)) {
                    $fail('The '.str_replace('_', ' ', $attribute).' is not available.');
                }
            }],
        ]);
        
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);       
        }
        
        $insertId = 0;
        $inputs = $request->all();
        $inputs['event_id'] = $eventArray['id'];
        $inputs['slot_duration'] = $eventArray['slot_duration'];
        $booking = Booking::create($inputs);
        $insertId = $booking->id;
        
        if ($insertId > 0) {
            return $this->sendResponse(['id'=>$insertId], 'Event scheduled successfully.');
        } else {
            return $this->sendError('Unexpected error.', ['Unexpected error occure. Please try again'], 400); 
        }
        
    }
    /** End **/
    
    private function parseEvent($singleEvent, $booking_date = null) {
        $temparray = [];
        if (!empty($singleEvent)) {
            $temparray = ['id' => $singleEvent['id'], 'name' => $singleEvent['name'], 'for_how_many_days' => $singleEvent['for_how_many_days'], 
            'slot_duration' => $singleEvent['slot_duration'], 'preparation_time' => $singleEvent['preparation_time'], 
            'booking_limit' => $singleEvent['booking_limit'], 'start_date' => $singleEvent['start_date'], 'end_date' => $singleEvent['end_date'], 
            'category_id' => $singleEvent['category_id'], 'category_name' => $singleEvent['category']['name']];
            
            $slotDuration = $singleEvent['slot_duration'];
            $booking_limit = $singleEvent['booking_limit'];
            /** booking counts according slots **/
            $bookings = $singleEvent['bookings'];
            $bookingSlotsCount = [];
            foreach ($bookings as $singleBooking) {
                $slotDateTime = $singleBooking['slot_date'].'-'.date('H:i', strtotime($singleBooking['slot_time']));
                $tempCount = ((!isset($bookingSlotsCount[$slotDateTime])) ? 0 : $bookingSlotsCount[$slotDateTime]['booked_cnt']);
                ++$tempCount;
                $bookingSlotsCount[$slotDateTime] = ['booked_cnt' => $tempCount, 'booking_left' => ($booking_limit - $tempCount)];
            }
            $temparray['booking_slots'] = $bookingSlotsCount;
            /** End **/
            
            $bookingDay = '';
            if($booking_date != null) {
                $booking_date = date('Y-m-d', strtotime($booking_date));
                $bookingDay = strtolower(date('l', strtotime($booking_date)));
            }
            /** unavailable slots **/
            $unavaliableSlotsArray = $singleEvent['unavailable_slots'];
            $temparray['unavailable_slots'] = [];
            foreach ($unavaliableSlotsArray as $singleSlot) {
                $tempDay = $singleSlot['active_day'];
                $slotStart = date('H:i', strtotime($singleSlot['slot_start_time']));
                $slotEnd = date('H:i', strtotime($singleSlot['slot_end_time']));
                
                if (strlen(trim($bookingDay)) <= 0 OR $bookingDay == $tempDay) {
                    $slotsArray = [];
                    $tempSlot = $slotStart;
                    do {
                        $slotsArray[] = $tempSlot;
                        $tempSlot = date('H:i', strtotime('+'.$slotDuration.' minutes', strtotime($tempSlot)));
                    } while ($tempSlot < $slotEnd);
                    
                    $temparray['unavailable_slots'][$tempDay] = ['name' => $singleSlot['name'], 'active_day' => $tempDay, 'slots' => $slotsArray];
                }
            }
            /** end **/
            
            /** available slots **/
            $avaliableSlotsArray = $singleEvent['available_slots'];
            $temparray['available_slots'] = [];
            foreach ($avaliableSlotsArray as $singleSlot) {
                $tempDay = $singleSlot['active_day'];
                $slotStart = date('H:i', strtotime($singleSlot['slot_start_time']));
                $slotEnd = date('H:i', strtotime($singleSlot['slot_end_time']));
                
                if (strlen(trim($bookingDay)) <= 0 OR $bookingDay == $tempDay) {
                    /** unavailableslots **/
                    $checkUnavailableSlots = [];
                    if (isset($temparray['unavailable_slots'][$tempDay])) {
                        $checkUnavailableSlots = $temparray['unavailable_slots'][$tempDay]['slots'];
                    }
                    /** end **/
                    
                    $slotsArray = [];
                    $tempSlot = $slotStart;
                    $bookingSlotsArray = $temparray['booking_slots'];
                    do {
                        $bookingLeft = $booking_limit;
                        if ($booking_date != null) {
                            $bookingIndex = $booking_date.'-'.$tempSlot;
                            if (isset($bookingSlotsArray[$bookingIndex])) {
                                $bookingLeft = (int) $bookingSlotsArray[$bookingIndex]['booking_left'];
                            }
                        }
                        
                        if (!in_array($tempSlot, $checkUnavailableSlots) && $bookingLeft > 0) { $slotsArray[] = $tempSlot; }
                        $tempSlot = date('H:i', strtotime('+'.$slotDuration.' minutes', strtotime($tempSlot)));
                    } while ($tempSlot < $slotEnd);
                    
                    $temparray['available_slots'][$tempDay] = ['name' => $singleSlot['name'], 'active_day' => $tempDay, 'slots' => $slotsArray];
                }
            }
            /** end **/
        }
        
        return $temparray;
    }
    
    /*
     Requirements:
    - maximum 2 sql queries
    - verify your solution with `php artisan test`
    - do a `git commit && git push` after you are done or when the time limit is over

    Hints:
    - open the `app/Http/Controllers/EventsController` file
    - partial or not working answers also get graded so make sure you commit what you have

    Sample response on GET /events:
    ```json
    [
        {
            "id": 1,
            "name": "Laravel convention 2020",
            "created_at": "2021-04-25T09:32:27.000000Z",
            "updated_at": "2021-04-25T09:32:27.000000Z",
            "workshops": [
                {
                    "id": 1,
                    "start": "2020-02-21 10:00:00",
                    "end": "2020-02-21 16:00:00",
                    "event_id": 1,
                    "name": "Illuminate your knowledge of the laravel code base",
                    "created_at": "2021-04-25T09:32:27.000000Z",
                    "updated_at": "2021-04-25T09:32:27.000000Z"
                }
            ]
        },
        {
            "id": 2,
            "name": "Laravel convention 2021",
            "created_at": "2021-04-25T09:32:27.000000Z",
            "updated_at": "2021-04-25T09:32:27.000000Z",
            "workshops": [
                {
                    "id": 2,
                    "start": "2021-10-21 10:00:00",
                    "end": "2021-10-21 18:00:00",
                    "event_id": 2,
                    "name": "The new Eloquent - load more with less",
                    "created_at": "2021-04-25T09:32:27.000000Z",
                    "updated_at": "2021-04-25T09:32:27.000000Z"
                },
                {
                    "id": 3,
                    "start": "2021-11-21 09:00:00",
                    "end": "2021-11-21 17:00:00",
                    "event_id": 2,
                    "name": "AutoEx - handles exceptions 100% automatic",
                    "created_at": "2021-04-25T09:32:27.000000Z",
                    "updated_at": "2021-04-25T09:32:27.000000Z"
                }
            ]
        },
        {
            "id": 3,
            "name": "React convention 2021",
            "created_at": "2021-04-25T09:32:27.000000Z",
            "updated_at": "2021-04-25T09:32:27.000000Z",
            "workshops": [
                {
                    "id": 4,
                    "start": "2021-08-21 10:00:00",
                    "end": "2021-08-21 18:00:00",
                    "event_id": 3,
                    "name": "#NoClass pure functional programming",
                    "created_at": "2021-04-25T09:32:27.000000Z",
                    "updated_at": "2021-04-25T09:32:27.000000Z"
                },
                {
                    "id": 5,
                    "start": "2021-08-21 09:00:00",
                    "end": "2021-08-21 17:00:00",
                    "event_id": 3,
                    "name": "Navigating the function jungle",
                    "created_at": "2021-04-25T09:32:27.000000Z",
                    "updated_at": "2021-04-25T09:32:27.000000Z"
                }
            ]
        }
    ]
     */

    public function getEventsWithWorkshops() {
        $events = Event::with('workshops')->get();
        return response()->json($events->toArray());
    }


    /*
    Requirements:
    - only events that have not yet started should be included
    - the event starting time is determined by the first workshop of the event
    - the eloquent expressions should result in maximum 3 SQL queries, no matter the amount of events
    - all filtering of records should happen in the database
    - verify your solution with `php artisan test`
    - do a `git commit && git push` after you are done or when the time limit is over

    Hints:
    - open the `app/Http/Controllers/EventsController` file
    - partial or not working answers also get graded so make sure you commit what you have
    - join, whereIn, min, groupBy, havingRaw might be helpful
    - in the sample data set  the event with id 1 is already in the past and should therefore be excluded

    Sample response on GET /futureevents:
    ```json
    [
        {
            "id": 2,
            "name": "Laravel convention 2021",
            "created_at": "2021-04-20T07:01:14.000000Z",
            "updated_at": "2021-04-20T07:01:14.000000Z",
            "workshops": [
                {
                    "id": 2,
                    "start": "2021-10-21 10:00:00",
                    "end": "2021-10-21 18:00:00",
                    "event_id": 2,
                    "name": "The new Eloquent - load more with less",
                    "created_at": "2021-04-20T07:01:14.000000Z",
                    "updated_at": "2021-04-20T07:01:14.000000Z"
                },
                {
                    "id": 3,
                    "start": "2021-11-21 09:00:00",
                    "end": "2021-11-21 17:00:00",
                    "event_id": 2,
                    "name": "AutoEx - handles exceptions 100% automatic",
                    "created_at": "2021-04-20T07:01:14.000000Z",
                    "updated_at": "2021-04-20T07:01:14.000000Z"
                }
            ]
        },
        {
            "id": 3,
            "name": "React convention 2021",
            "created_at": "2021-04-20T07:01:14.000000Z",
            "updated_at": "2021-04-20T07:01:14.000000Z",
            "workshops": [
                {
                    "id": 4,
                    "start": "2021-08-21 10:00:00",
                    "end": "2021-08-21 18:00:00",
                    "event_id": 3,
                    "name": "#NoClass pure functional programming",
                    "created_at": "2021-04-20T07:01:14.000000Z",
                    "updated_at": "2021-04-20T07:01:14.000000Z"
                },
                {
                    "id": 5,
                    "start": "2021-08-21 09:00:00",
                    "end": "2021-08-21 17:00:00",
                    "event_id": 3,
                    "name": "Navigating the function jungle",
                    "created_at": "2021-04-20T07:01:14.000000Z",
                    "updated_at": "2021-04-20T07:01:14.000000Z"
                }
            ]
        }
    ]
    ```
     */

    public function getFutureEventsWithWorkshops() {
        $whereCondition = [['start', '>', date('Y-m-d H:i:s')]];
        $events = Event::with(['workshops' => function ($query) use ($whereCondition) {
            $query->where($whereCondition);
            $query->orderBy('start');
        }])->whereHas('workshops', function ($query) use ($whereCondition) {
            $query->where($whereCondition);
        })->get();
        return response()->json($events->toArray());
    }
}
