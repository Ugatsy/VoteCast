<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/student/login', [Student\AuthController::class, 'showLanding'])->name('student.landing');
Route::post('/verify', [Student\AuthController::class, 'verify'])->name('student.verify');
Route::post('/student/logout', [Student\AuthController::class, 'logout'])->name('student.logout');

Route::middleware('student')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/vote/{votingSession}', [Student\VotingBallotController::class, 'show'])->name('student.ballot');
    Route::post('/vote/{votingSession}', [Student\VotingBallotController::class, 'submit'])->name('student.vote');
    Route::get('/confirmation', [Student\VotingBallotController::class, 'confirmation'])->name('student.confirmation');
    Route::get('/results/{session}', [Student\DashboardController::class, 'getLiveResults'])->name('student.results.live');
    Route::get('/receipt/{session}', [Student\VotingBallotController::class, 'getReceipt'])->name('student.receipt');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login']);
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [Admin\EnrollmentController::class, 'index'])->name('index');
            Route::post('/upload', [Admin\EnrollmentController::class, 'upload'])->name('upload');
            Route::post('/semester', [Admin\EnrollmentController::class, 'setSemester'])->name('semester');
        });

        Route::get('/sessions', [Admin\VotingSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/create', [Admin\VotingSessionController::class, 'create'])->name('sessions.create');
        Route::post('/sessions', [Admin\VotingSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{votingSession}', [Admin\VotingSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{votingSession}/status', [Admin\VotingSessionController::class, 'updateStatus'])->name('sessions.status');
        Route::get('/sessions/{votingSession}/results', [Admin\VotingSessionController::class, 'results'])->name('sessions.results');

        Route::get('/sessions/{votingSession}/candidates', [Admin\VotingSessionController::class, 'candidates'])->name('sessions.candidates');
        Route::post('/sessions/{votingSession}/positions', [Admin\VotingSessionController::class, 'addPosition'])->name('sessions.positions.add');
        Route::delete('/positions/{position}', [Admin\VotingSessionController::class, 'deletePosition'])->name('positions.delete');
        Route::post('/positions/{position}/candidates', [Admin\VotingSessionController::class, 'addCandidate'])->name('positions.candidates.add');
        Route::delete('/candidates/{candidate}', [Admin\VotingSessionController::class, 'removeCandidate'])->name('candidates.delete');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/sessions/{votingSession}/votes', [Admin\VotingSessionController::class, 'getVoteStats'])
                ->name('session.votes');
        });
    });
});
