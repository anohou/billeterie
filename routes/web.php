<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SupervisorDashboardController;
use App\Http\Controllers\SellerDashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/home', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/admin', [AdminDashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Admin CRUDs
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('stations', \App\Http\Controllers\Admin\StationController::class);
        Route::resource('stops', \App\Http\Controllers\Admin\StopController::class);
        Route::resource('vehicle-types', \App\Http\Controllers\Admin\VehicleTypeController::class);
        Route::resource('vehicles', \App\Http\Controllers\Admin\VehicleController::class);
        Route::resource('routes', \App\Http\Controllers\Admin\RouteController::class);
        
        // Route Stops Management
        Route::get('routes/{route}/stops', [\App\Http\Controllers\Admin\RouteStopOrderController::class, 'index'])->name('routes.stops.index');
        Route::post('routes/{route}/stops', [\App\Http\Controllers\Admin\RouteStopOrderController::class, 'store'])->name('routes.stops.store');
        Route::delete('routes/{route}/stops/{routeStopOrder}', [\App\Http\Controllers\Admin\RouteStopOrderController::class, 'destroy'])->name('routes.stops.destroy');
        Route::put('routes/{route}/stops/reorder', [\App\Http\Controllers\Admin\RouteStopOrderController::class, 'reorder'])->name('routes.stops.reorder');

        Route::resource('trips', \App\Http\Controllers\Admin\TripController::class);
        Route::resource('route-fares', \App\Http\Controllers\Admin\RouteFareController::class);
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::put('users/{user}/toggle-active', [\App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::resource('assignments', \App\Http\Controllers\Admin\UserAssignmentController::class)->only(['index','store','update','destroy']);
        
        // Ticket Settings
        Route::get('ticket-settings', [\App\Http\Controllers\Admin\TicketSettingController::class, 'index'])->name('ticket-settings.index');
        Route::put('ticket-settings', [\App\Http\Controllers\Admin\TicketSettingController::class, 'update'])->name('ticket-settings.update');
    });

    Route::get('/supervisor', [SupervisorDashboardController::class, 'index'])
        ->middleware('role:admin,supervisor')
        ->name('supervisor.dashboard');

    Route::get('/seller', [SellerDashboardController::class, 'index'])
        ->middleware('role:admin,supervisor,seller')
        ->name('seller.dashboard');

    // Ticketing routes
    Route::middleware('role:admin,supervisor,seller')->group(function () {
        Route::get('/ticketing', [\App\Http\Controllers\Seller\TicketingController::class, 'index'])->name('seller.ticketing');
        Route::get('/ticketing-horizontal', [\App\Http\Controllers\Seller\TicketingController::class, 'horizontal'])->name('seller.ticketing.horizontal');
        Route::get('/seller/tickets', [\App\Http\Controllers\Seller\TicketController::class, 'index'])->name('seller.tickets.index');
        Route::get('/trips', [\App\Http\Controllers\Api\TripController::class, 'index'])->name('trips.index');
        Route::get('/tickets', [\App\Http\Controllers\Api\TicketController::class, 'index'])->name('tickets.index');
    });

    // API-like endpoints for ticketing
    // API-like endpoints for ticketing
    Route::post('/seller/tickets', [\App\Http\Controllers\Api\TicketController::class, 'store'])
        ->middleware('auth')
        ->name('tickets.store');
    Route::delete('/seller/tickets/{ticket}', [\App\Http\Controllers\Api\TicketController::class, 'destroy'])
        ->middleware('auth')
        ->name('tickets.destroy');
    Route::get('/seller/trips/{trip}/seat-map', [\App\Http\Controllers\Api\TripController::class, 'seatMap'])
        ->middleware('auth')
        ->name('trips.seatmap');
    Route::get('/seller/trips/{trip}/suggest-seats', [\App\Http\Controllers\Api\TripController::class, 'suggestSeats'])
        ->middleware('auth')
        ->name('api.trips.suggest-seats');
    
    // Seller trip creation
    Route::post('/seller/trips', [\App\Http\Controllers\Admin\TripController::class, 'store'])
        ->middleware('auth')
        ->name('seller.trips.store');
    
    // Routes d'impression
    Route::get('/tickets/{ticket}/print', [\App\Http\Controllers\TicketPrintController::class, 'print'])
        ->middleware('auth')
        ->name('tickets.print');
    Route::post('/tickets/print-multiple', [\App\Http\Controllers\TicketPrintController::class, 'printMultiple'])
        ->middleware('auth')
        ->name('tickets.print-multiple');
});

require __DIR__.'/auth.php';
