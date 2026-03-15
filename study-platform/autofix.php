<!DOCTYPE html>
<html>
<head>
    <title>StudyVault — Auto Fix</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        h1 { font-size: 28px; margin-bottom: 6px; color: #60a5fa; }
        .sub { color: #64748b; margin-bottom: 32px; font-size: 14px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 16px; }
        .card h2 { font-size: 16px; margin-bottom: 16px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #1e293b; }
        .row:last-child { border-bottom: none; }
        .label { font-size: 14px; color: #cbd5e1; }
        .pass  { color: #34d399; font-weight: bold; font-size: 13px; }
        .fail  { color: #f87171; font-weight: bold; font-size: 13px; }
        .warn  { color: #fbbf24; font-weight: bold; font-size: 13px; }
        .fix-btn { display: block; width: 100%; padding: 16px; background: #2563eb; border: none; border-radius: 10px; color: #fff; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
        .fix-btn:hover { background: #1d4ed8; }
        .fix-btn:disabled { background: #334155; cursor: not-allowed; }
        .result { margin-top: 16px; padding: 16px; border-radius: 10px; font-size: 14px; display: none; line-height: 1.8; }
        .result.success { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.3); color: #34d399; }
        .result.error   { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3); color: #f87171; }
        .creds { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin-top: 12px; }
        .creds p { font-size: 14px; margin-bottom: 6px; }
        .creds strong { color: #60a5fa; }
        .go-btn { display: inline-block; margin-top: 12px; padding: 12px 28px; background: #16a34a; border-radius: 8px; color: #fff; text-decoration: none; font-weight: bold; font-size: 15px; }
        code { background: #0f172a; padding: 2px 8px; border-radius: 4px; font-size: 13px; color: #a78bfa; }
    </style>
</head>
<body>
<?php
// ── Checks ───────────────────────────────────────
$checks = [];

// 1. PHP version
$checks['php'] = ['label' => 'PHP Version', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '7.0', '>=')];

// 2. MySQLi extension
$checks['mysqli'] = ['label' => 'MySQLi Extension', 'value' => extension_loaded('mysqli') ? 'Loaded' : 'NOT loaded', 'ok' => extension_loaded('mysqli')];

// 3. DB connection
$conn = @new mysqli('localhost', 'root', '', '');
$dbConnected = ($conn->connect_error === null);
$checks['db'] = ['label' => 'MySQL Connection (localhost / root)', 'value' => $dbConnected ? 'Connected' : 'FAILED: ' . $conn->connect_error, 'ok' => $dbConnected];

// 4. Database exists
$dbExists = false;
if ($dbConnected) {
    $r = $conn->query("SHOW DATABASES LIKE 'study_platform'");
    $dbExists = $r && $r->num_rows > 0;
}
$checks['dbexists'] = ['label' => 'Database study_platform exists', 'value' => $dbExists ? 'Yes' : 'No', 'ok' => $dbExists];

// 5. Tables exist
$tablesOk = false;
if ($dbExists) {
    $conn->select_db('study_platform');
    $r = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $r->fetch_row()) $tables[] = $row[0];
    $tablesOk = in_array('users', $tables) && in_array('notes', $tables) && in_array('categories', $tables);
    $checks['tables'] = ['label' => 'Tables (users, notes, categories)', 'value' => $tablesOk ? 'All exist' : 'Missing: ' . implode(', ', array_diff(['users','notes','categories'], $tables)), 'ok' => $tablesOk];
} else {
    $checks['tables'] = ['label' => 'Tables (users, notes, categories)', 'value' => 'DB not found', 'ok' => false];
}

// 6. Admin user exists
$adminExists = false;
$adminHash   = '';
if ($tablesOk) {
    $r = $conn->query("SELECT id, password FROM users WHERE email = 'admin@studyvault.com'");
    $adminExists = $r && $r->num_rows > 0;
    if ($adminExists) $adminHash = $r->fetch_assoc()['password'];
}
$checks['admin'] = ['label' => 'Admin user exists', 'value' => $adminExists ? 'Found' : 'NOT found', 'ok' => $adminExists];

// 7. Password hash valid
$hashValid = $adminExists && password_verify('admin123', $adminHash);
$checks['hash'] = ['label' => 'Admin password hash valid', 'value' => $hashValid ? 'Valid (admin123)' : ($adminExists ? 'INVALID HASH — needs fix' : 'N/A'), 'ok' => $hashValid];

// 8. Uploads folder
$uploadsPath = __DIR__ . '/uploads';
$uploadsOk = is_dir($uploadsPath);
if (!$uploadsOk) { mkdir($uploadsPath, 0755, true); $uploadsOk = is_dir($uploadsPath); }
$checks['uploads'] = ['label' => 'uploads/ folder', 'value' => $uploadsOk ? 'Exists (created if missing)' : 'Cannot create', 'ok' => $uploadsOk];

// ── Auto-Fix Logic ────────────────────────────────
$fixDone    = false;
$fixMessage = '';
$fixError   = '';

if (isset($_POST['autofix'])) {
    try {
        if (!$dbConnected) throw new Exception("Cannot connect to MySQL. Make sure XAMPP MySQL is running.");

        // Create DB
        $conn->query("CREATE DATABASE IF NOT EXISTS study_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db('study_platform');

        // Create tables
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("DROP TABLE IF EXISTS notes");
        $conn->query("DROP TABLE IF EXISTS users");
        $conn->query("DROP TABLE IF EXISTS categories");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        $conn->query("CREATE TABLE users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            email      VARCHAR(100) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            role       ENUM('student','admin') NOT NULL DEFAULT 'student',
            last_login DATETIME DEFAULT NULL,
            is_online  TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE categories (
            id   INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE notes (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(200) NOT NULL,
            subject     VARCHAR(100) NOT NULL,
            category_id INT DEFAULT NULL,
            file_name   VARCHAR(255) NOT NULL,
            file_path   VARCHAR(255) NOT NULL,
            file_size   BIGINT DEFAULT 0,
            uploaded_by INT NOT NULL,
            downloads   INT DEFAULT 0,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by)  REFERENCES users(id)      ON DELETE CASCADE,
            FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insert categories
        $cats = ['Programming','Mathematics','Physics','Web Technologies','Database','Other'];
        foreach ($cats as $cat) {
            $conn->query("INSERT IGNORE INTO categories (name) VALUES ('$cat')");
        }

        // Insert admin with fresh hash
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $conn->query("DELETE FROM users WHERE email = 'admin@studyvault.com'");
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@studyvault.com', ?, 'admin')");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $stmt->close();

        // Create uploads folder
        if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);

        $fixDone    = true;
        $fixMessage = "✅ Database created\n✅ Tables created (users, categories, notes)\n✅ 6 categories inserted\n✅ Admin account created with fresh password hash\n✅ uploads/ folder ready";
    } catch (Exception $e) {
        $fixError = $e->getMessage();
    }
}
?>

<div class="container">
    <h1>🔧 StudyVault Auto-Fix</h1>
    <p class="sub">This tool diagnoses and fixes all database issues automatically.</p>

    <!-- Checks -->
    <div class="card">
        <h2>System Checks</h2>
        <?php foreach ($checks as $key => $c): ?>
        <div class="row">
            <span class="label"><?= $c['label'] ?></span>
            <span class="<?= $c['ok'] ? 'pass' : 'fail' ?>"><?= $c['ok'] ? '✅ ' : '❌ ' ?><?= htmlspecialchars($c['value']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Fix Button -->
    <div class="card">
        <h2>Auto Fix Everything</h2>
        <p style="font-size:14px; color:#94a3b8; margin-bottom:16px;">Click the button below — it will create the database, all tables, categories, and admin account automatically.</p>
        <form method="POST">
            <button type="submit" name="autofix" class="fix-btn">🚀 Fix Everything Now</button>
        </form>

        <?php if ($fixDone): ?>
        <div class="result success" style="display:block">
            <pre style="white-space:pre-wrap"><?= $fixMessage ?></pre>
            <div class="creds">
                <p>🔑 Admin Login Credentials:</p>
                <p><strong>Email:</strong> admin@studyvault.com</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
            <a href="login.php" class="go-btn">→ Go to Login Page</a>
        </div>
        <?php elseif ($fixError): ?>
        <div class="result error" style="display:block">❌ <?= htmlspecialchars($fixError) ?></div>
        <?php endif; ?>
    </div>

    <!-- Instructions -->
    <div class="card">
        <h2>After Fixing</h2>
        <div class="row"><span class="label">Admin Login</span><span style="font-size:13px; color:#60a5fa">admin@studyvault.com / admin123</span></div>
        <div class="row"><span class="label">Register new student</span><span style="font-size:13px; color:#60a5fa">Go to register.php → fill form</span></div>
        <div class="row"><span class="label">Delete this file after</span><span class="warn">⚠️ Remove autofix.php when done</span></div>
    </div>
</div>
</body>
</html>