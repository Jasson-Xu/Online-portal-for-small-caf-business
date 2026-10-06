<?php
// Manual setup only. setup-xampp.cmd creates local.php automatically.
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'cafe_portal',
    'db_user' => 'cafe_app',
    'db_password' => 'replace-with-your-database-password',
    'session_secure' => false, // Set true when served over HTTPS.
];
