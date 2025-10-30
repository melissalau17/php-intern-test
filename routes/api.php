<?php
use App\Http\Controllers\EmployeeController;

Route::prefix('employees')->group(function () {
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/{nomor}', [EmployeeController::class, 'show']);
    Route::put('/{nomor}', [EmployeeController::class, 'update']);
    Route::delete('/{nomor}', [EmployeeController::class, 'destroy']);
});