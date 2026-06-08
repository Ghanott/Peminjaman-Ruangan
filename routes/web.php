<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\ItemController as MasterItemController;
use App\Http\Controllers\Master\RoomBlackoutController as MasterRoomBlackoutController;
use App\Http\Controllers\Master\RoomController as MasterRoomController;
use App\Http\Controllers\Master\RoomOpeningHourController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('rooms/availability', RoomAvailabilityController::class)
        ->name('rooms.availability');

    Route::get('bookings/approval-queue', [BookingController::class, 'approvalQueue'])
        ->name('bookings.approval-queue');
    Route::get('bookings/approval-queue/export', [BookingController::class, 'exportApprovalQueueCsv'])
        ->name('bookings.approval-queue.export');
    Route::get('bookings/approval-queue/export-xlsx', [BookingController::class, 'exportApprovalQueueXlsx'])
        ->name('bookings.approval-queue.export.xlsx');
    Route::get('bookings/kasubbag-queue', [BookingController::class, 'kasubbagQueue'])
        ->name('bookings.kasubbag-queue');
    Route::get('bookings/kasubbag-queue/export', [BookingController::class, 'exportKasubbagQueueCsv'])
        ->name('bookings.kasubbag-queue.export');
    Route::get('bookings/kasubbag-queue/export-xlsx', [BookingController::class, 'exportKasubbagQueueXlsx'])
        ->name('bookings.kasubbag-queue.export.xlsx');
    Route::get('bookings/{booking}/kasubbag-review', [BookingController::class, 'kasubbagReview'])
        ->name('bookings.kasubbag-review');
    Route::get('bookings/{booking}/spr/download', [BookingController::class, 'downloadSpr'])
        ->name('bookings.spr.download');
    Route::get('bookings/{booking}/attachments/{attachment}/download', [BookingController::class, 'downloadAttachment'])
        ->name('bookings.attachments.download');
    Route::delete('bookings/{booking}/attachments/{attachment}', [BookingController::class, 'destroyAttachment'])
        ->name('bookings.attachments.destroy');

    Route::resource('bookings', BookingController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('bookings/{booking}/action', [BookingController::class, 'action'])
        ->name('bookings.action');
    Route::post('bookings/{booking}/resubmit', [BookingController::class, 'resubmit'])
        ->name('bookings.resubmit');

    Route::middleware('can:manage-master-data')
        ->prefix('master')
        ->name('master.')
        ->group(function () {
            Route::resource('rooms', MasterRoomController::class)
                ->only(['index', 'create', 'store', 'edit', 'update']);
            Route::get('rooms/{room}/opening-hours', [RoomOpeningHourController::class, 'edit'])
                ->name('rooms.opening-hours.edit');
            Route::put('rooms/{room}/opening-hours', [RoomOpeningHourController::class, 'update'])
                ->name('rooms.opening-hours.update');

            Route::resource('blackouts', MasterRoomBlackoutController::class)
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

            Route::resource('items', MasterItemController::class)
                ->only(['index', 'create', 'store', 'edit', 'update']);
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
