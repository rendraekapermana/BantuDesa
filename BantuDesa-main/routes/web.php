<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\Web3Controller; 
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
});
/****************************/
/***** ADMIN GUEST ENDS *****/
/****************************/


/*****************************/
/***** ADMIN AUTH START ******/
/*****************************/
Route::prefix('admin')->middleware('auth')->name('auth.')->group(function () {
    // Dashboard & Auth
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/change-password', [UserController::class, 'changePassword'])->name('changePassword');
    Route::post('/change-password', [UserController::class, 'changePasswordSubmit'])->name('changePassword-submit');
    Route::get('/logout', [UserController::class, 'logout'])->name('logout');

    // Donation & Leaderboard Management
    Route::get('/donation', [DonationController::class, 'donation'])->name('donation');
    Route::delete('/donation', [DonationController::class, 'delete'])->name('donation.delete');
    Route::put('/leaderboard-status', [DonationController::class, 'leaderBoardStatus'])->name('donation.leaderboard-status');

    // Queries, Albums, Members (Existing Routes)
    Route::get('/queries', [QueryController::class, 'queries'])->name('queries');
    Route::delete('/queries', [QueryController::class, 'delete'])->name('querie.delete');
    
    Route::get('/albums', [AlbumController::class, 'albums'])->name('albums');
    Route::delete('/albums', [AlbumController::class, 'deleteAlbums'])->name('albums.delete');
    Route::post('/albums', [AlbumController::class, 'addAlbums'])->name('albums.add');
    Route::put('/albums', [AlbumController::class, 'updateAlbums'])->name('albums.update');
    Route::get('/album/detail', [AlbumController::class, 'albumDetail'])->name('album.detail');
    Route::delete('/media', [AlbumController::class, 'deleteMedia'])->name('media.delete');
    Route::post('/upload/media/{type}', [AlbumController::class, 'uploadCKEditorMedia'])->name('upload.media');

    Route::get('/members', [PagesController::class, 'members'])->name('members');
    Route::post('/members', [PagesController::class, 'addMembers'])->name('members.add');
    Route::put('/members', [PagesController::class, 'updateMember'])->name('members.update');
    Route::delete('/members', [PagesController::class, 'deleteMembers'])->name('members.delete');

    // ============================================================
    // CAMPAIGNS (DESA) MANAGEMENT - CRUD
    // ============================================================
    // Resource ini otomatis membuat rute: auth.campaigns.index, create, store, edit, update, destroy
    Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class);

    // ============================================================
    // CAMPAIGN WITHDRAWAL (PENARIKAN DANA)
    // ============================================================
    // [FIX] Rute ini dipindahkan ke dalam grup ADMIN agar aman & nama route-nya konsisten
    // Nama akhir akan menjadi: auth.campaigns.withdraw
    Route::get('campaigns/{id}/withdraw', [\App\Http\Controllers\Admin\CampaignController::class, 'withdraw'])->name('campaigns.withdraw');
    Route::post('campaigns/{id}/withdraw', [\App\Http\Controllers\Admin\CampaignController::class, 'withdrawStore'])->name('campaigns.withdraw.store');
});
/*****************************/
/****** ADMIN AUTH ENDS ******/
/*****************************/


/*****************************/
/**** LANDING PAGES START ****/
/*****************************/

// Home & Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('contact', [HomeController::class, 'contact'])->name('home.contact');
Route::get('about', [HomeController::class, 'about'])->name('home.about');
Route::get('privacy', [HomeController::class, 'privacy'])->name('home.privacy-policy');

// Leaderboard & Albums
Route::get('leaderboard', [HomeController::class, 'leaderboard'])->name('home.leaderboard');
Route::get('albums', [HomeController::class, 'albums'])->name('home.albums');
Route::get('album/{id}', [HomeController::class, 'album'])->name('home.album');

// Contact Submission
Route::post('contact', [HomeController::class, 'contactSubmit'])->name('home.contact.submit');

// ========================================================
// DONATION & CAMPAIGN FRONTEND
// ========================================================

// 1. Halaman Daftar Desa (Grid View)
Route::get('donate', [HomeController::class, 'donate'])->name('home.donate');

// 2. Halaman Detail Campaign (Cerita & Rincian Dana)
Route::get('campaign/{id}', [HomeController::class, 'showCampaignDetail'])->name('home.campaign.detail');

// 3. Halaman Form Pembayaran (Spesifik per Desa)
Route::get('donate/{id}', [HomeController::class, 'showDonateForm'])->name('home.donate.form');


// ========================================================
// MIDTRANS PAYMENT ROUTES
// ========================================================
Route::post('process-checkout-midtrans', [CheckoutController::class, 'createSession'])->name('process.checkout.midtrans');
Route::post('midtrans/callback', [CheckoutController::class, 'handleCallback'])->name('midtrans.callback');
Route::get('midtrans/finish', [CheckoutController::class, 'finishPayment'])->name('midtrans.finish');
Route::get('midtrans/error', [CheckoutController::class, 'errorPayment'])->name('midtrans.error');
Route::get('midtrans/pending', [CheckoutController::class, 'pendingPayment'])->name('midtrans.pending');

// ========================================================
// BLOCKCHAIN PROCESS ROUTES
// ========================================================
// Trigger manual (opsional, biasanya otomatis via Midtrans callback)
Route::post('process-donation-onchain', [DonationController::class, 'processDonation'])->name('donation.process');

// Konfirmasi & Sukses Page
Route::get('donation/confirm-onchain/{id}', [DonationController::class, 'confirmOnChain'])->name('donation.confirm_onchain');
Route::get('donation/complete/{id}', [DonationController::class, 'donationComplete'])->name('donation.complete');

// Endpoint API Web3 (Opsional)
Route::post('/web3/record-success', [Web3Controller::class, 'recordSuccess'])->name('web3.recordSuccess');

// Route Legacy Stripe (Bisa dihapus jika tidak dipakai)
Route::get('payment-success', [CheckoutController::class, 'paymentSuccess'])->name('stripe.success');
Route::get('failed-payment', [CheckoutController::class, 'handleFailedPayment'])->name('stripe.payment');

// Route AJAX Lokasi (Dicomment karena fitur alamat dihapus)
// Route::get('find/states', [HomeController::class, 'findStates'])->name('find.states');
// Route::get('find/cities', [HomeController::class, 'findCities'])->name('find.cities');