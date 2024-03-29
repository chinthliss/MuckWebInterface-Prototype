<?php

use App\Http\Controllers\MuckRequestsController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
// Pages that are freely available:
Route::get('terms-of-service-hash', 'Auth\TermsOfServiceController@getHash');
Route::get('terms-of-service', 'Auth\TermsOfServiceController@getContent');

// Pages that only the muck can use:
Route::prefix('muck/')->middleware(['muck.verified'])->group(function () {
    Route::post('test', [MuckRequestsController::class, 'test'])
        ->name('muck.test');
});

// Pages that require an api token
Route::group(['middleware' => ['auth:api']], function() {

});


