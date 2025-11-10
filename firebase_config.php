<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Firebase config yang aman - ganti dengan konfigurasi Firebase Anda
$config = [
    'apiKey' => getenv('FIREBASE_API_KEY') ?: 'AIzaSyDMsnNTL2vnajEwkNY59WEgJvKzB_w7Vng',
    'authDomain' => getenv('FIREBASE_AUTH_DOMAIN') ?: 'lokasi-client-tyar.firebaseapp.com',
    'projectId' => getenv('FIREBASE_PROJECT_ID') ?: 'lokasi-client-tyar',
    'storageBucket' => getenv('FIREBASE_STORAGE_BUCKET') ?: 'lokasi-client-tyar.firebasestorage.app',
    'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID') ?: '53755341759',
    'appId' => getenv('FIREBASE_APP_ID') ?: '1:53755341759:web:31b674d9b032f6e6674f0a'
];

echo json_encode($config);