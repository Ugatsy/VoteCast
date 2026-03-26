<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', [Student\AuthController::class, 'showLanding'])->name('student.landing');
Route::post('/verify', [Student\AuthController::class, 'verify'])->name('student.verify');
Route::post('/student/logout', [Student\AuthController::class, 'logout'])->name('student.logout');

Route::middleware('student')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/vote/{votingSession}', [Student\VotingBallotController::class, 'show'])->name('student.ballot');
    Route::post('/vote/{votingSession}', [Student\VotingBallotController::class, 'submit'])->name('student.vote');
    Route::get('/confirmation', [Student\VotingBallotController::class, 'confirmation'])->name('student.confirmation');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Public admin routes (no auth required)
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login']);
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware('admin')->group(function () {

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // ── Enrollment ─────────────────────────────────────────────────────
        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [Admin\EnrollmentController::class, 'index'])->name('index');
            Route::post('/upload', [Admin\EnrollmentController::class, 'upload'])->name('upload');
            Route::post('/semester', [Admin\EnrollmentController::class, 'setSemester'])->name('semester');
        });

        // ── Voting Sessions ────────────────────────────────────────────────
        Route::get('/sessions', [Admin\VotingSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/create', [Admin\VotingSessionController::class, 'create'])->name('sessions.create');
        Route::post('/sessions', [Admin\VotingSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{votingSession}', [Admin\VotingSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{votingSession}/status', [Admin\VotingSessionController::class, 'updateStatus'])->name('sessions.status');
        Route::get('/sessions/{votingSession}/results', [Admin\VotingSessionController::class, 'results'])->name('sessions.results');

        // ── Positions & Candidates ─────────────────────────────────────────
        Route::get('/sessions/{votingSession}/candidates', [Admin\VotingSessionController::class, 'candidates'])->name('sessions.candidates');
        Route::post('/sessions/{votingSession}/positions', [Admin\VotingSessionController::class, 'addPosition'])->name('sessions.positions.add');
        Route::delete('/positions/{position}', [Admin\VotingSessionController::class, 'deletePosition'])->name('positions.delete');
        Route::post('/positions/{position}/candidates', [Admin\VotingSessionController::class, 'addCandidate'])->name('positions.candidates.add');
        Route::delete('/candidates/{candidate}', [Admin\VotingSessionController::class, 'removeCandidate'])->name('candidates.delete');

        // ── API Routes for Real-time Updates ───────────────────────────────
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/sessions/{votingSession}/votes', [Admin\VotingSessionController::class, 'getVoteStats'])
                ->name('session.votes');
        });
    });
});
