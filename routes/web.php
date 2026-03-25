<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CourseList;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('cursos', CourseList::class)->name('courses');
});

require __DIR__.'/settings.php';
