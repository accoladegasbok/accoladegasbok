<?php

use App\Http\Controllers\PartsSearchController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\CustomerCreditAdminController;
use App\Http\Controllers\Admin\AiFitmentReviewController;
use App\Http\Controllers\Admin\SubscriberAdminController;
use App\Http\Controllers\Admin\PartRequestAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/parts', [PartsSearchController::class, 'index'])->name('parts.search');
Route::post('/parts/vin-decode', [PartsSearchController::class, 'vinDecode'])->name('parts.vin-decode');
Route::get('/parts/models', [PartsSearchController::class, 'modelsByMake'])->name('parts.models');
Route::get('/parts/search', [PartsSearchController::class, 'ajaxSearch'])->name('parts.ajax-search');

// NEW: Consumables/Electronics/Computers/Other — grouped separately
// from automobile parts, since /parts should read as a used-auto-
// parts site, not a general goods marketplace.
Route::get('/parts/categories', [PartsSearchController::class, 'categories'])->name('parts.categories');
Route::get('/parts/sold', [PartsSearchController::class, 'soldAll'])->name('parts.sold');
Route::get('/other-items', [PartsSearchController::class, 'otherItems'])->name('other-items');

// NEW: "Request this part" — a customer who cannot find a part (or looked at a sold one) leaves
// their details. Limited to 6 submissions per 10 minutes per visitor to stop spam.
Route::post('/parts/request', [PartRequestController::class, 'store'])
    ->middleware('throttle:6,10')
    ->name('parts.request.store');

// NEW: the staff inbox for those requests. Staff login required (same guards as the rest of /admin).
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/part-requests')->name('admin.part-requests.')->group(function () {
    Route::get('/',      [PartRequestAdminController::class, 'index'])->name('index');
    Route::post('/{id}', [PartRequestAdminController::class, 'update'])->name('update')->whereNumber('id');
});

// NEW: "Subscribe to our emails" (footer box) and the one-click unsubscribe link.
Route::post('/subscribe', [SubscribeController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('subscribe.store');
Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe'])->name('subscribe.unsubscribe');

// NEW: staff view of the email list + CSV download (the page itself limits it to supervisor and above).
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/subscribers')->name('admin.subscribers.')->group(function () {
    Route::get('/',       [SubscriberAdminController::class, 'index'])->name('index');
    Route::get('/export', [SubscriberAdminController::class, 'export'])->name('export');
});

// NEW: public policy pages (linked from every footer; the receipts point to /warranty).
Route::get('/refund-policy',   [PolicyController::class, 'refund'])->name('policy.refund');
Route::get('/shipping-policy', [PolicyController::class, 'shipping'])->name('policy.shipping');
Route::get('/warranty',        [PolicyController::class, 'warranty'])->name('policy.warranty');

// NEW: customer credit (overpayments + return store-credit). Viewing and paying out need supervisor or above;
// the invoice form's balance lookup is open to any signed-in staff. The controller enforces the role.
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/customer-credit')->name('admin.customer-credit.')->group(function () {
    Route::get('/',        [CustomerCreditAdminController::class, 'index'])->name('index');
    Route::get('/lookup',  [CustomerCreditAdminController::class, 'lookup'])->name('lookup');
    Route::post('/adjust', [CustomerCreditAdminController::class, 'adjust'])->name('adjust');
    Route::post('/recheck', [CustomerCreditAdminController::class, 'recheck'])->name('recheck');
    Route::get('/{phoneKey}',         [CustomerCreditAdminController::class, 'show'])->name('show')->where('phoneKey', '[0-9]+');
    Route::post('/{phoneKey}/payout', [CustomerCreditAdminController::class, 'payout'])->name('payout')->where('phoneKey', '[0-9]+');
});

// NEW: AI fitment review — batch select, a mandatory second review step, audit log and the 30-day report.
// The controller limits every action to supervisor and above.
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/ai-fitment')->name('admin.ai-fitment.')->group(function () {
    Route::get('/',        [AiFitmentReviewController::class, 'index'])->name('index');
    Route::post('/review', [AiFitmentReviewController::class, 'review'])->name('review');
    Route::get('/review',  [AiFitmentReviewController::class, 'showReview'])->name('review.show');
    Route::post('/confirm',[AiFitmentReviewController::class, 'confirm'])->name('confirm');
    Route::post('/reject', [AiFitmentReviewController::class, 'reject'])->name('reject');
    Route::get('/report',  [AiFitmentReviewController::class, 'report'])->name('report');
});

// NEW: Awaiting Approval — new stock and restocks wait here for a manager or admin (the controller enforces that).
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/part-approvals')->name('admin.part-approvals.')->group(function () {
    Route::get('/',              [\App\Http\Controllers\Admin\PartApprovalController::class, 'index'])->name('index');
    Route::post('/approve',      [\App\Http\Controllers\Admin\PartApprovalController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject',  [\App\Http\Controllers\Admin\PartApprovalController::class, 'reject'])->name('reject')->where('id', '[0-9]+');
});

// NEW: Fitment tools - pin counts with donor VIN + connector photo, VIN prefill, staged fitment review.
// Any signed-in staff member can record a pin count; only a manager or admin can verify (the controller enforces that).
Route::middleware(['admin.auth'])->prefix('admin/fitment')->name('admin.fitment.')->group(function () {
    $c = \App\Http\Controllers\Admin\FitmentController::class;
    Route::get('/',                         [$c, 'index'])->name('index');
    Route::get('/decode-vin',               [$c, 'decodeVin'])->name('decode-vin');
    Route::post('/pins',                    [$c, 'storePin'])->name('pins.store');
    Route::post('/pins/{id}/verify',        [$c, 'verifyPin'])->name('pins.verify')->where('id', '[0-9]+');
    Route::post('/pins/{id}/reject',        [$c, 'rejectPin'])->name('pins.reject')->where('id', '[0-9]+');
    Route::delete('/pins/{id}',             [$c, 'deletePin'])->name('pins.delete')->where('id', '[0-9]+');
    Route::post('/staging/{id}/{decision}', [$c, 'stagingDecision'])->name('staging.decide')->where('id', '[0-9]+');
});

// NEW: quick restock of an existing consumable (adds units under the same number; staff below manager need approval).
Route::middleware(['admin.auth', 'stocking-clerk'])->post('admin/consumables/{id}/add-stock', [\App\Http\Controllers\Admin\ConsumableController::class, 'addStock'])
    ->name('admin.inventory.consumable.addstock')->where('id', '[0-9]+');

// NEW: set the size / volume of a consumable (applies to the same product at every location).
Route::middleware(['admin.auth', 'stocking-clerk'])->post('admin/consumables/{id}/set-size', [\App\Http\Controllers\Admin\ConsumableController::class, 'setSize'])
    ->name('admin.inventory.consumable.setsize')->where('id', '[0-9]+');

// NEW: stock an existing consumable/electronics/computer/other item at another location - nothing retyped, same number.
Route::middleware(['admin.auth', 'stocking-clerk'])->post('admin/consumables/{id}/stock-at-location', [\App\Http\Controllers\Admin\ConsumableController::class, 'stockAtLocation'])
    ->name('admin.inventory.consumable.stockat')->where('id', '[0-9]+');

// Import a new batch of an item that was entered before (pre-typed list; nothing re-entered).
Route::middleware(['admin.auth', 'stocking-clerk'])->get('admin/consumables/import-batch', [\App\Http\Controllers\Admin\ConsumableController::class, 'importBatchForm'])
    ->name('admin.inventory.consumable.importbatch');
Route::middleware(['admin.auth', 'stocking-clerk'])->post('admin/consumables/import-batch', [\App\Http\Controllers\Admin\ConsumableController::class, 'importBatch'])
    ->name('admin.inventory.consumable.importbatch.store');

// NEW: Attendance - phone fingerprint (passkey) + selfie + location clock-in. Every signed-in staff member can clock;
// the report is supervisor and above; sites and phone approval are manager and admin (the controller enforces each).
Route::middleware(['admin.auth'])->prefix('admin/attendance')->name('admin.attendance.')->group(function () {
    $c = \App\Http\Controllers\Admin\AttendanceController::class;
    Route::get('/',                    [$c, 'clock'])->name('clock');
    Route::get('/challenge',           [$c, 'challenge'])->name('challenge');
    Route::post('/in',                 [$c, 'clockIn'])->name('in');
    Route::post('/out',                [$c, 'clockOut'])->name('out');
    Route::post('/passkey/register',   [$c, 'registerPasskey'])->name('passkey.register');
    Route::get('/report',              [$c, 'report'])->name('report');
    Route::post('/excuse',             [$c, 'excuse'])->name('excuse');
    Route::get('/photo/{id}/{which}',  [$c, 'photo'])->name('photo')->where('id', '[0-9]+')->where('which', 'in|out');
    Route::get('/settings',            [$c, 'settings'])->name('settings');
    Route::post('/settings',           [$c, 'saveSite'])->name('site.save');
    Route::post('/passkey/{id}/approve', [$c, 'approvePasskey'])->name('passkey.approve')->where('id', '[0-9]+');
    Route::post('/passkey/{id}/revoke',  [$c, 'revokePasskey'])->name('passkey.revoke')->where('id', '[0-9]+');
});

// NEW: Staff training (LMS) - courses, assignments with due dates, video + quiz, compliance board, certificates.
// Overdue training turns the person RED and blocks clock-in (see AttendanceController).
Route::middleware(['admin.auth'])->prefix('admin/training')->name('admin.training.')->group(function () {
    $c = \App\Http\Controllers\Admin\TrainingController::class;
    Route::get('/',                          [$c, 'index'])->name('index');
    Route::get('/mine',                      [$c, 'mine'])->name('mine');
    Route::get('/courses/create',            [$c, 'create'])->name('create');
    Route::post('/courses',                  [$c, 'save'])->name('store');
    Route::get('/courses/{id}/edit',         [$c, 'edit'])->name('edit')->where('id', '[0-9]+');
    Route::post('/courses/{id}',             [$c, 'save'])->name('update')->where('id', '[0-9]+');
    Route::post('/courses/{id}/toggle',      [$c, 'toggle'])->name('toggle')->where('id', '[0-9]+');
    Route::get('/assign',                    [$c, 'assignForm'])->name('assign');
    Route::post('/assign',                   [$c, 'assign'])->name('assign.store');
    Route::delete('/assignments/{id}',       [$c, 'destroyAssignment'])->name('assignment.delete')->where('id', '[0-9]+');
    Route::post('/assignments/{id}/complete',[$c, 'markComplete'])->name('complete')->where('id', '[0-9]+');
    Route::get('/watch/{id}',                [$c, 'watch'])->name('watch')->where('id', '[0-9]+');
    Route::post('/watch/{id}/ping',          [$c, 'ping'])->name('ping')->where('id', '[0-9]+');
    Route::post('/watch/{id}/submit',        [$c, 'submit'])->name('submit')->where('id', '[0-9]+');
    Route::get('/video/{courseId}',          [$c, 'video'])->name('video')->where('courseId', '[0-9]+');
    Route::get('/certificate/{id}',          [$c, 'certificate'])->name('certificate')->where('id', '[0-9]+');
});

// NEW: Employee of the Month - monthly score, top 3 nominees, one vote per staff member, winner and rewards.
Route::middleware(['admin.auth'])->prefix('admin/employee-of-the-month')->name('admin.eotm.')->group(function () {
    $c = \App\Http\Controllers\Admin\EotmController::class;
    Route::get('/',            [$c, 'index'])->name('index');
    Route::get('/rate',        [$c, 'rateForm'])->name('rate');
    Route::post('/rate',       [$c, 'rateSave'])->name('rate.save');
    Route::post('/shortlist',  [$c, 'shortlist'])->name('shortlist');
    Route::post('/vote',       [$c, 'vote'])->name('vote');
    Route::post('/close',      [$c, 'close'])->name('close');
    Route::post('/reward',     [$c, 'reward'])->name('reward');
    Route::get('/certificate/{cycleId}', [$c, 'certificate'])->name('certificate')->where('cycleId', '[0-9]+');
});

// NEW: sitemap for search engines (home, main pages, policies and every part that is available).
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
