<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderUserController;
use App\Http\Controllers\Provider\ProviderRouteController;
use App\Http\Controllers\Provider\ProviderVehicleController;
use App\Http\Controllers\Provider\ChauffeurRouteController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\SettingController;


Route::get('/', function () {
    return view('welcome');
});

// Language switch MUST be here
 //----------------
    //Language
    //----------------
// LANGUAGE SWITCH ROUTE — MUST USE WEB MIDDLEWARE
Route::middleware(['web'])->get('/lang/{locale}', function ($locale) {
    if (!in_array($locale, ['en', 'fr'])) {
        $locale = 'en';
    }
    session(['locale' => $locale]);  // Saves locale to session
    app()->setLocale($locale);       // Sets locale for the *current* request

    return redirect()->back();

})->name('lang.switch');



Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

//Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::get('/test-session', function () {
    session(['locale' => 'fr']);
    return session()->all();
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/create-user-go', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/create-user-go', [AuthController::class, 'register'])->name('register.submit');


// Admin routes-------------------------------------------------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});
Route::middleware(['admin'])->group(function() {
   
    //----------------
    //USERS
    //----------------
    Route::get('/admin/users', [AdminController::class, 'usersList'])
        ->name('admin.users.list');
    Route::get('/admin/user/{id}', [AdminController::class, 'viewUser'])
    ->name('admin.user.view');

    //----------------
    //PROVIDERS
    //----------------
     Route::get('/admin/providers', [ProviderController::class, 'index'])
        ->name('admin.providers.list');

    Route::get('/admin/providers/create', [ProviderController::class, 'create'])
        ->name('admin.providers.create');

    Route::post('/admin/providers/store', [ProviderController::class, 'store'])
        ->name('admin.providers.store');

        Route::delete('/providers/{provider}', [ProviderController::class, 'destroy'])
    ->name('admin.providers.destroy');

        // Provider detail
    Route::get('/admin/providers/{id}', [ProviderController::class, 'show'])
        ->name('admin.providers.show');

    // Provider Users Tab
    Route::get('/admin/providers/{provider}/users', [ProviderUserController::class, 'index'])
        ->name('admin.providers.users');

    Route::get('/admin/providers/{provider}/trips',
        [ProviderController::class, 'trips'])
        ->name('admin.providers.trips');

    Route::get('/admin/providers/{provider}/vehicles',
        [ProviderController::class, 'vehicles'])
        ->name('admin.providers.vehicles');

    Route::get('/admin/providers/{provider}/routes',
        [ProviderController::class, 'routes'])
        ->name('admin.providers.routes');
    Route::get('admin/providers/{provider}/car-vehicles', [ProviderController::class, 'carVehicles'])->name('admin.providers.car-vehicles');
    Route::get('admin/providers/{provider}/car-routes',   [ProviderController::class, 'carRoutes'])->name('admin.providers.car-routes');
    Route::get('admin/providers/{provider}/car-drivers',  [ProviderController::class, 'carDrivers'])->name('admin.providers.car-drivers');
    Route::get('admin/providers/{provider}/car-bookings', [ProviderController::class, 'carBookings'])->name('admin.providers.car-bookings');
//---------------------------------------------------------------------------------
    // Create new provider-role user
    Route::get('/admin/provider-users/create', [ProviderUserController::class, 'create'])
        ->name('admin.provider-users.create');

    Route::post('/admin/provider-users/store', [ProviderUserController::class, 'store'])
        ->name('admin.provider-users.store');

    // Assign user to provider
    Route::post('/admin/providers/{provider}/assign-user', [ProviderUserController::class, 'assignUser'])
        ->name('admin.providers.assignUser');

    // Remove provider-user assignment
    Route::delete('/admin/providers/{provider}/remove-user/{user}', [ProviderUserController::class, 'removeUser'])
        ->name('admin.providers.removeUser');

        // routes/web.php
    Route::post('admin/providers/{provider}/update', [ProviderController::class, 'update'])
        ->name('admin.providers.update');
    Route::get('admin/providers/{provider}/edit', [ProviderController::class, 'edit'])
        ->name('admin.providers.edit');



    // Cities
    Route::get('/admin/cities', [CityController::class, 'index'])
    ->name('admin.cities.index');

    Route::get('/admin/cities/create', [CityController::class, 'create'])
        ->name('admin.cities.create');

    Route::post('/admin/cities/store', [CityController::class, 'store'])
        ->name('admin.cities.store');

    Route::get('/admin/cities/{id}/edit', [CityController::class, 'edit'])
        ->name('admin.cities.edit');

    Route::post('/admin/cities/{id}/update', [CityController::class, 'update'])
        ->name('admin.cities.update');

    Route::delete('/admin/cities/{id}', [CityController::class, 'destroy'])
        ->name('admin.cities.delete');
    // Setings
    // Route::prefix('admin/settings')->group(function () {

    // Route::get('/', [SettingController::class, 'index'])
    //     ->name('admin.settings.index');

    // Route::get('/{id}/edit', [SettingController::class, 'edit'])
    //     ->name('admin.settings.edit');

    // Route::put('/{id}', [SettingController::class, 'update'])
    //     ->name('admin.settings.update');

    // });
    Route::resource('settings', SettingController::class);
});
// Provider Routes ------------------------------------------------------------------------------------------------------------------------------------------
Route::middleware(['auth', 'role:provider'])
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('provider.dashboard');
        })->name('dashboard');

        // VEHICLES CRUD
        Route::resource('vehicles', \App\Http\Controllers\Provider\ProviderVehicleController::class);
        Route::delete('/vehicles/image/{id}', [ProviderVehicleController::class, 'deleteImage'])
            ->name('provider.vehicle.image.delete');
        //Trips
        Route::resource('trips', \App\Http\Controllers\Provider\ProviderTripController::class);

        //Routes
       Route::get('/routes', [ProviderRouteController::class, 'index'])
        ->name('routes.index');

    Route::get('/routes/create', [ProviderRouteController::class, 'create'])
        ->name('routes.create');

    Route::post('/routes/store', [ProviderRouteController::class, 'store'])
        ->name('routes.store');

    Route::get('/routes/{id}/edit', [ProviderRouteController::class, 'edit'])
        ->name('routes.edit');

    Route::put('/routes/{id}', [ProviderRouteController::class, 'update'])
        ->name('routes.update');

    Route::delete('/routes/{id}', [ProviderRouteController::class, 'destroy'])
        ->name('routes.destroy');

        // Profile routes — add these lines inside the provider group above:
 
    Route::get('/profile', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'edit'])
        ->name('profile.edit');
    
    Route::put('/profile', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'update'])
        ->name('profile.update');
    
    // Agency / Provider info (managers only — enforced in controller)
    Route::put('/profile/agency', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'updateAgency'])
        ->name('profile.agency');
    
    // Password change
    Route::put('/profile/password', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'updatePassword'])
        ->name('profile.password');
    
    // Activity log
    Route::get('/profile/activity', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'activity'])
        ->name('profile.activity');

    });

// Provider routes
Route::middleware(['auth', 'role:provider'])->prefix('provider')->group(function () {
    Route::get('/dashboard', function () {
        return view('provider.dashboard');
    })->name('provider.dashboard');
});

Route::prefix('provider/chauffeur')->name('provider.chauffeur.')->middleware(['auth', 'role:provider'])->group(function () {

    Route::resource('vehicles', \App\Http\Controllers\Provider\ChauffeurVehicleController::class);

});
Route::prefix('provider')
    ->name('provider.')
    ->middleware(['auth'])
    ->group(function () {

        Route::prefix('chauffeur')
            ->name('chauffeur.')
            ->group(function () {

                Route::resource('routes', 
                   ChauffeurRouteController::class
                );

            });

});
Route::prefix('provider')
->name('provider.')
->middleware(['auth'])
->group(function () {

    Route::prefix('chauffeur')
        ->name('chauffeur.')
        ->group(function () {

            Route::resource('drivers',
                \App\Http\Controllers\Provider\ChauffeurDriverController::class
            );

        });

});
Route::delete(
    'provider/chauffeur/routes/{id}',
    [ChauffeurRouteController::class, 'destroy']
)->name('provider.chauffeur.routes.destroy');