<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Command line only.'); }

function setupFailure(string $message): never { fwrite(STDERR, "Setup failed: $message\n"); exit(1); }
if (PHP_VERSION_ID < 80200 || !extension_loaded('pdo_mysql')) setupFailure('XAMPP needs PHP 8.2 or newer with PDO MySQL enabled.');
if (count($argv) > 1) setupFailure('Run setup-xampp.cmd without arguments.');

$port = getenv('XAMPP_DB_PORT') ?: '3306';
if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) setupFailure('XAMPP_DB_PORT must be a valid port number.');
$dsn = "mysql:host=127.0.0.1;port=$port;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$localPath = __DIR__ . '/../config/local.php';
$settings = is_file($localPath) ? require $localPath : [];
$reuse = is_array($settings) && ($settings['db_host'] ?? '') === '127.0.0.1'
    && (string)($settings['db_port'] ?? '') === $port
    && ($settings['db_name'] ?? '') === 'cafe_portal'
    && is_string($settings['db_user'] ?? null)
    && str_starts_with($settings['db_user'], 'cafe_app');
$app = null;
if ($reuse) {
    try { $app = new PDO($dsn . ';dbname=cafe_portal', (string)$settings['db_user'], (string)($settings['db_password'] ?? ''), $options); }
    catch (PDOException) { $reuse = false; }
}

if (!$reuse) {
    $adminUser = getenv('XAMPP_DB_ADMIN_USER') ?: 'root';
    $adminPassword = getenv('XAMPP_DB_ADMIN_PASSWORD') ?: '';
    try { $admin = new PDO($dsn, $adminUser, $adminPassword, $options); }
    catch (PDOException) { setupFailure("Cannot connect to XAMPP MySQL on port $port. Start MySQL in the Control Panel. If its administrator has a password, set XAMPP_DB_ADMIN_PASSWORD before running setup."); }
    $appUser = 'cafe_app_' . bin2hex(random_bytes(4));
    $appPassword = bin2hex(random_bytes(20));
    try {
        $admin->exec('CREATE DATABASE IF NOT EXISTS cafe_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $admin->exec("CREATE USER '$appUser'@'127.0.0.1' IDENTIFIED BY " . $admin->quote($appPassword));
        $admin->exec("GRANT ALL PRIVILEGES ON cafe_portal.* TO '$appUser'@'127.0.0.1'");
        $app = new PDO($dsn . ';dbname=cafe_portal', $appUser, $appPassword, $options);
    } catch (PDOException $error) { setupFailure('The database administrator could not create the café database and its dedicated account: ' . $error->getMessage()); }

    if (is_file($localPath)) {
        $backup = __DIR__ . '/../var/local-config-before-xampp-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.php';
        if (!copy($localPath, $backup)) setupFailure('Could not back up the existing local database settings. No settings were replaced.');
        echo "Previous local settings backed up in var/.\n";
    }
    $settings = [
        'db_host' => '127.0.0.1',
        'db_port' => $port,
        'db_name' => 'cafe_portal',
        'db_user' => $appUser,
        'db_password' => $appPassword,
        'session_secure' => false,
    ];
    $contents = "<?php\nreturn " . var_export($settings, true) . ";\n";
    if (file_put_contents($localPath, $contents, LOCK_EX) === false) setupFailure('Could not write config/local.php.');
    echo "Created a dedicated café database account.\n";
} else {
    echo "Using the existing café database account.\n";
}

try {
    foreach (['schema', 'seed'] as $file) $app->exec(file_get_contents(__DIR__ . "/../database/$file.sql"));
    $email = 'staff@example.test';
    $stmt = $app->prepare('SELECT role FROM users WHERE email=?');
    $stmt->execute([$email]);
    $existingStaff = $stmt->fetch();
    if ($existingStaff && $existingStaff['role'] !== 'staff') setupFailure('staff@example.test belongs to a customer. Choose a different staff email with scripts/create-staff.php.');
    if (!$existingStaff) {
        $staffPassword = 'Cafe-' . bin2hex(random_bytes(12));
        $stmt = $app->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'staff')");
        $stmt->execute(['Cafe staff', $email, password_hash($staffPassword, PASSWORD_DEFAULT)]);
        echo "Staff email: $email\nStaff password: $staffPassword\nSave this password; it is shown only now.\n";
    } else {
        echo "Existing staff email: $email (password unchanged).\n";
    }
    echo "Database and sample menu ready. Existing orders were retained.\n";
} catch (PDOException $error) { setupFailure('Could not install the café tables or staff account: ' . $error->getMessage()); }
