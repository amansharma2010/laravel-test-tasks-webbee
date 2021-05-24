<?php

use App\Http\Controllers\EventsController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/schedule-event/{id}', [EventsController::class, 'postScheduleEvent']);
Route::get('/event/{id}', [EventsController::class, 'getSingleEvent']);
Route::get('/events', [EventsController::class, 'getEvents']);

Route::get('/eventswithworkshops', [EventsController::class, 'getEventsWithWorkshops']);
Route::get('/futureevents', [EventsController::class, 'getFutureEventsWithWorkshops']);
Route::get('/menu', [MenuController::class, 'getMenuItems']);
