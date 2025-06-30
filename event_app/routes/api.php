<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventsController;
use App\Models\Event;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

//// Auth routes
Route::post('/member-registration', [AuthController::class, 'memberRegistration']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user/{id}', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*******
 * * Users
 */
//get all users
Route::get('/users', [UsersController::class, 'getUsers']);
//get single user
//Route::get('/user/{id}', [UsersController::class, 'getUser']);

//create user
Route::post('/create-user', [UsersController::class, 'createUser']);
//update user
Route::put('/update-user/{id}', [UsersController::class, 'updateUser']);
//delete user
Route::delete('/delete-user/{id}', [UsersController::class, 'deleteUser']);

Route::post('/send-otp', [AuthController::class, 'sendOTPCode']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

/*****
* 
** Events
*/
Route::post('/event', [EventsController::class, 'store']);
Route::get('/events', [EventsController::class, 'events']);
Route::get('/event/{event}', [EventsController::class, 'getevent']);
Route::put('/event/update/{id}', [EventsController::class, 'updateEvent']);
Route::post('/events', [EventsController::class, 'createEvent']); 
Route::delete('/events/{id}', [EventsController::class, 'deleteEvent']);

/*****
 * 
 * * Bookings
 */

//get all bookings
Route::get('/bookings', [BookingsController::class, 'getAllBookings']);
Route::get('/booking/{id}', [BookingsController::class, 'getBooking']);
Route::post('/member-event-booking', [BookingsController::class, 'store']);
Route::get('/member-event-bookings/{id}', [BookingsController::class, 'getMemberbookings']);
Route::put('/booking/update/{id}', [BookingsController::class, 'updateBooking']);

// New routes for event availability checking
Route::post('/check-event-availability', [BookingsController::class, 'checkEventAvailability']);
Route::post('/auto-expire-bookings', [BookingsController::class, 'autoExpireBookings']);