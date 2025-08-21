<?php

declare(strict_types=1);

return [
    'login' => [
        'title' => 'Sign In',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Sign In',
        'forgot_password' => 'Forgot your password?',
    ],

    'register' => [
        'title' => 'Register',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'submit' => 'Register',
        'already_registered' => 'Already registered?',
    ],

    'forgot_password' => [
        'title' => 'Forgot Password',
        'description' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
        'email' => 'Email',
        'submit' => 'Email Password Reset Link',
    ],

    'reset_password' => [
        'title' => 'Reset Password',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'submit' => 'Reset Password',
    ],

    'verify_email' => [
        'title' => 'Email Verification',
        'description' => 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
        'notification_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend Verification Email',
        'logout' => 'Log Out',
    ],

    'confirm_password' => [
        'title' => 'Confirm Password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'password' => 'Password',
        'submit' => 'Confirm',
    ],

    'messages' => [
        'failed' => 'These credentials do not match our records.',
        'password_reset_sent' => 'We have emailed your password reset link!',
        'password_reset' => 'Your password has been reset!',
        'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        'verified' => 'Your email has been verified.',
        'verification_sent' => 'A fresh verification link has been sent to your email address.',
    ],
];