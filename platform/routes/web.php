<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityMediaController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtistProfileController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityChannelController;
use App\Http\Controllers\CommunityMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ModerationReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileReviewController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WordPressPublicationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('password/change', [PasswordChangeController::class, 'show'])
    ->middleware(['auth'])
    ->name('password.change');

Route::post('password/change', [PasswordChangeController::class, 'store'])
    ->middleware(['auth'])
    ->name('password.change.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    Route::resource('artists', ArtistController::class);
    Route::post('artists/{artist}/wordpress/publish', [WordPressPublicationController::class, 'publishArtist'])->name('artists.wordpress.publish');
    Route::patch('artists/{artist}/wordpress/unpublish', [WordPressPublicationController::class, 'unpublishArtist'])->name('artists.wordpress.unpublish');

    Route::resource('activities', ActivityController::class);
    Route::patch('activities/{activity}/publish', [ActivityController::class, 'publish'])->name('activities.publish');
    Route::patch('activities/{activity}/archive', [ActivityController::class, 'archive'])->name('activities.archive');
    Route::post('activities/{activity}/wordpress/publish', [WordPressPublicationController::class, 'publishActivity'])->name('activities.wordpress.publish');
    Route::patch('activities/{activity}/wordpress/unpublish', [WordPressPublicationController::class, 'unpublishActivity'])->name('activities.wordpress.unpublish');

    Route::post('activities/{activity}/media', [ActivityMediaController::class, 'store'])->name('activities.media.store');
    Route::patch('activities/{activity}/media/{media}/cover', [ActivityMediaController::class, 'setCover'])->name('activities.media.cover');
    Route::delete('activities/{activity}/media/{media}', [ActivityMediaController::class, 'destroy'])->name('activities.media.destroy');
    Route::post('activities/{activity}/media/reorder', [ActivityMediaController::class, 'reorder'])->name('activities.media.reorder');

    Route::get('profile/edit', [ArtistProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile/edit', [ArtistProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/submit', [ArtistProfileController::class, 'submit'])->name('profile.submit');

    Route::get('profile-reviews', [ProfileReviewController::class, 'index'])->name('profile-reviews.index');
    Route::get('profile-reviews/{artist}', [ProfileReviewController::class, 'show'])->name('profile-reviews.show');
    Route::patch('profile-reviews/{artist}/approve', [ProfileReviewController::class, 'approve'])->name('profile-reviews.approve');
    Route::patch('profile-reviews/{artist}/reject', [ProfileReviewController::class, 'reject'])->name('profile-reviews.reject');
    Route::patch('profile-reviews/{artist}/request-changes', [ProfileReviewController::class, 'requestChanges'])->name('profile-reviews.request-changes');

    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::resource('proposals', ProposalController::class);
    Route::patch('proposals/{proposal}/submit', [ProposalController::class, 'submit'])->name('proposals.submit');
    Route::get('proposal-reviews', [ProposalReviewController::class, 'index'])->name('proposal-reviews.index');
    Route::patch('proposal-reviews/{proposal}/start', [ProposalReviewController::class, 'start'])->name('proposal-reviews.start');
    Route::patch('proposal-reviews/{proposal}/approve', [ProposalReviewController::class, 'approve'])->name('proposal-reviews.approve');
    Route::patch('proposal-reviews/{proposal}/reject', [ProposalReviewController::class, 'reject'])->name('proposal-reviews.reject');
    Route::patch('proposal-reviews/{proposal}/request-changes', [ProposalReviewController::class, 'requestChanges'])->name('proposal-reviews.request-changes');

    Route::resource('imports', ImportController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('imports/{import}/process', [ImportController::class, 'process'])->name('imports.process');

    Route::get('exports/artists', [ExportController::class, 'artists'])->name('exports.artists');
    Route::get('exports/activities', [ExportController::class, 'activities'])->name('exports.activities');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('community/channels', [CommunityChannelController::class, 'index'])->name('community.channels.index');
    Route::get('community/channels/{channel}', [CommunityChannelController::class, 'show'])->name('community.channels.show');
    Route::post('community/channels/{channel}/messages', [CommunityMessageController::class, 'store'])->name('community.messages.store');
    Route::post('community/messages/{message}/reports', [ModerationReportController::class, 'store'])->name('moderation-reports.store');
    Route::get('moderation-reports', [ModerationReportController::class, 'index'])->name('moderation-reports.index');
    Route::patch('moderation-reports/{report}/resolve', [ModerationReportController::class, 'resolve'])->name('moderation-reports.resolve');
});

require __DIR__.'/auth.php';
