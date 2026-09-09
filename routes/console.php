<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create {--email=} {--password=} {--name=}', function () {
    $name = $this->option('name') ?: $this->ask('Admin Full Name', 'CarelioEMR Super Admin');
    $email = $this->option('email') ?: $this->ask('Admin Email Address', 'admin@carelioemr.com');
    $password = $this->option('password') ?: $this->secret('Admin Password');

    if (empty($email) || empty($password)) {
        $this->error('Email and password cannot be empty!');
        return 1;
    }

    $user = \App\Models\User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'email_verified_at' => now(),
        ]
    );

    $this->info("Administrator [{$user->email}] successfully saved to database!");
    return 0;
})->purpose('Create or update an administrator account safely without hardcoding in .env');

