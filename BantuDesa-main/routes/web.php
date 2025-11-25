<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\Web3Controller; // PENTING: Pastikan ini ada
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/****************************/
/**** ADMIN GUEST START *****/
/****************************/
Route::prefix('admin')->middleware('guest')->group(function () {
    Route::get('/', [UserController::class, 'signin'])->name('signin');
    Route::post('/signin', [UserController::class, 'signinSubmit'])->name('signin-submit');
    // Rute forgot password dan reset password di-comment out, dibiarkan
});
/****************************/
/***** ADMIN GUEST ENDS *****/
/****************************/


/*****************************/
/***** ADMIN AUTH START ******/
/*****************************/
Route::prefix('admin')->middleware('auth')->name('auth.')->group(function () {
    // Rute Admin yang sudah ada (dashboard, logout, change password, etc.)
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/change-password', [UserController::class, 'changePassword'])->name('changePassword');
    Route::post('/change-password', [UserController::class, 'changePasswordSubmit'])->name('changePassword-submit');
    Route::get('/logout', [UserController::class, 'logout'])->name('logout');

    // Rute Admin Donasi & Leaderboard
    Route::get('/donation', [ DonationController::class, 'donation'])->name('donation');
    Route::delete('/donation', [ DonationController::class, 'delete'])->name('donation.delete');
    Route::put('/leaderboard-status', [ DonationController::class, 'leaderBoardStatus'])->name('donation.leaderboard-status');
    
    // Rute Admin Query, Album, Member, dll. (Dibiarkan)
    Route::get('/queries', [ QueryController::class, 'queries'])->name('queries');
    Route::delete('/queries', [ QueryController::class, 'delete'])->name('querie.delete');
    Route::get('/albums', [ AlbumController::class, 'albums'])->name('albums');
    Route::delete('/albums', [ AlbumController::class, 'deleteAlbums'])->name('albums.delete');
    Route::post('/albums', [ AlbumController::class, 'addAlbums'])->name('albums.add');
    Route::put('/albums', [ AlbumController::class, 'updateAlbums'])->name('albums.update');
    Route::get('/album/detail', [ AlbumController::class, 'albumDetail'])->name('album.detail');
    Route::delete('/media', [ AlbumController::class, 'deleteMedia'])->name('media.delete');
    Route::get('/members', [ PagesController::class, 'members'])->name('members');
    Route::post('/members', [ PagesController::class, 'addMembers'])->name('members.add');
    Route::put('/members', [ PagesController::class, 'updateMember'])->name('members.update');
    Route::delete('/members', [ PagesController::class, 'deleteMembers'])->name('members.delete');
    Route::post('/upload/media/{type}', [ AlbumController::class, 'uploadCKEditorMedia'])->name('upload.media');
});
/*****************************/
/****** ADMIN AUTH ENDS ******/
/*****************************/


/*****************************/
/**** LANDING PAGES START ****/
/*****************************/
# Home & Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('contact', [HomeController::class, 'contact'])->name('home.contact');
Route::get('about', [HomeController::class, 'about'])->name('home.about');
Route::get('privacy', [HomeController::class, 'privacy'])->name('home.privacy-policy');

# Leaderboard & Album
Route::get('leaderboard', [HomeController::class, 'leaderboard'])->name('home.leaderboard'); // RUTE YANG MENGALAMI ERROR
Route::get('albums', [HomeController::class, 'albums'])->name('home.albums');
Route::get('album/{id}', [HomeController::class, 'album'])->name('home.album');

# Halaman Form Donasi
Route::get('donate', [HomeController::class, 'donate'])->name('home.donate');

# Contact Form Submission
Route::post('contact', [HomeController::class, 'contactSubmit'])->name('home.contact.submit');

// ========================================================
// MIDTRANS PAYMENT ROUTES
// ========================================================

// Route untuk memproses checkout dengan Midtrans
Route::post('process-checkout-midtrans', [CheckoutController::class, 'createSession'])->name('process.checkout.midtrans');

// Route untuk callback dari Midtrans (Webhook)
Route::post('midtrans/callback', [CheckoutController::class, 'handleCallback'])->name('midtrans.callback');

// Route untuk finish payment (redirect dari Snap)
Route::get('midtrans/finish', [CheckoutController::class, 'finishPayment'])->name('midtrans.finish');

// Route untuk error payment
Route::get('midtrans/error', [CheckoutController::class, 'errorPayment'])->name('midtrans.error');

// Route untuk pending payment
Route::get('midtrans/pending', [CheckoutController::class, 'pendingPayment'])->name('midtrans.pending');

# ========================================================
# ALUR DONASI BLOCKCHAIN BARU
# ========================================================

// 1. Rute POST Donasi (Pemicu Alur Blockchain)
Route::post('process-donation-onchain', [DonationController::class, 'processDonation'])->name('donation.process');

// 2. Rute Konfirmasi Metamask (Menampilkan Blade confirm_onchain)
Route::get('donation/confirm-onchain/{id}', [DonationController::class, 'confirmOnChain'])->name('donation.confirm_onchain');

// 3. Rute Sukses Akhir (Dipanggil dari JavaScript setelah Hash Tersimpan)
Route::get('donation/complete/{id}', [DonationController::class, 'donationComplete'])->name('donation.complete');

# ========================================================
# Rute Checkout Lama (Dibiarkan untuk referensi atau dihapus)
# ========================================================

// Route::post('process-checkout', [CheckoutController::class, 'createSession'])->name('process.checkout');
Route::get('payment-success', [CheckoutController::class, 'paymentSuccess'])->name('stripe.success');
Route::get('failed-payment',  [CheckoutController::class, 'handleFailedPayment'])->name('stripe.payment');

# Extra: Select2, ...
// Route::get('find/countries',  [HomeController::class, 'findCountries'])->name('find.countries');
Route::get('find/states',  [HomeController::class, 'findStates'])->name('find.states');
Route::get('find/cities',  [HomeController::class, 'findCities'])->name('find.cities');
/*****************************/
/**** LANDING PAGES ENDS *****/
/*****************************/

// Endpoint API Web3 untuk menerima hash (Secara teknis harus di routes/api.php, tapi akan berfungsi di sini)
Route::post('/web3/record-success', [Web3Controller::class, 'recordSuccess'])->name('web3.recordSuccess');