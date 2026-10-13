<?php

declare(strict_types=1);

use App\Livewire\Coordinator\IssueAssignmentQueue;
use App\Livewire\Coordinator\IssueReviewQueue;
use App\Livewire\Reporter\ConfirmationQueue;
use App\Livewire\Technician\IssueQueue;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/coordinator/issues/review', IssueReviewQueue::class)
        ->middleware('role:coordinator,admin,super_admin')
        ->name('coordinator.issues.review');

    Route::get('/coordinator/issues/assign', IssueAssignmentQueue::class)
        ->middleware('role:coordinator,admin,super_admin')
        ->name('coordinator.issues.assign');

    Route::get('/technician/issues', IssueQueue::class)
        ->middleware('role:technician')
        ->name('technician.issues.index');

    Route::get('/reporter/issues/confirmation', ConfirmationQueue::class)
        ->middleware('role:member')
        ->name('reporter.issues.confirmation');
});
