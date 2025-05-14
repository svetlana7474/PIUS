use App\Http\Controllers\Api\BlowfishController;

Route::get('v1/blowfish', [BlowfishController::class, 'process']);
