<?php

declare(strict_types=1);

return [
    'login' => [
        'title' => 'Sign In',
        'page_title' => 'Sign in to your account',
        'heading' => 'Sign in to your account',
        'subheading' => 'Welcome back! Please enter your details.',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Sign In',
        'forgot_password' => 'Forgot your password?',
        'no_account' => 'Don\'t have an account?',
        'sign_up' => 'Sign up',
    ],
    'register' => [
        'title' => 'Register',
        'page_title' => 'Create your account',
        'heading' => 'Create your account',
        'subheading' => 'Get started with your free account today',
        'name' => 'Name',
        'username' => 'Username',
        'username_placeholder' => 'username_123',
        'email' => 'Email',
        'email_address' => 'Email Address',
        'email_placeholder' => 'Enter your email',
        'password' => 'Password',
        'password_placeholder' => 'Create a strong password',
        'password_confirmation' => 'Confirm Password',
        'submit' => 'Register',
        'already_registered' => 'Already registered?',
        'already_have_account' => 'Already have an account?',
        'sign_in' => 'Sign in',
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
    'profile' => [
        'title' => 'Profile',
        'sections' => [
            'general' => 'General',
            'teams' => 'Teams',
        ],
        'actions' => [
            'edit' => 'Update info',
        ],
        'fields' => [
            'name' => 'Name',
            'avatar' => 'Personal avatar',
            'timezone' => 'Timezone',
        ],
    ],
];
