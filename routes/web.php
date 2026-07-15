<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.dashboard');
Route::get('/whatsapp/status', [WhatsAppController::class, 'status'])->name('whatsapp.status');
Route::post('/whatsapp/sidecar/start', [WhatsAppController::class, 'startSidecar'])->name('whatsapp.sidecar.start');
Route::post('/whatsapp/sidecar/stop', [WhatsAppController::class, 'stopSidecar'])->name('whatsapp.sidecar.stop');
Route::post('/whatsapp/session/start', [WhatsAppController::class, 'startSession'])->name('whatsapp.session.start');
Route::post('/whatsapp/session/destroy', [WhatsAppController::class, 'destroySession'])->name('whatsapp.session.destroy');
Route::post('/whatsapp/send', [WhatsAppController::class, 'sendMessage'])->name('whatsapp.send');

