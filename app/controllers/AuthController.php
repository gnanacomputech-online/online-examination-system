<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            flash_set('error', 'Invalid email or password, or account is inactive.');
            redirect('login');
        }

        redirect(Auth::dashboardRoute(Auth::role()));
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        session_start();
        flash_set('success', 'You have been logged out.');
        redirect('login');
    }
}
