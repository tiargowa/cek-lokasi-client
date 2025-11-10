<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Firebase config yang aman - ganti dengan konfigurasi Firebase Anda
$config = [
    'apiKey' => getenv('FIREBASE_API_KEY') ?: 'YOUR-FIREBASE-API-KEY',
    'authDomain' => getenv('FIREBASE_AUTH_DOMAIN') ?: 'YOUR-PROJECT-ID.firebaseapp.com',
    'projectId' => getenv('FIREBASE_PROJECT_ID') ?: 'YOUR-PROJECT-ID',
    'storageBucket' => getenv('FIREBASE_STORAGE_BUCKET') ?: 'YOUR-PROJECT-ID.appspot.com',
    'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID') ?: 'YOUR-SENDER-ID',
    'appId' => getenv('FIREBASE_APP_ID') ?: 'YOUR-APP-ID'
];

echo json_encode($config);