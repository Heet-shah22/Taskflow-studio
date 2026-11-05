<?php
// config/db.php - Database Connection Configuration

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'taskflow_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a PDO database connection instance.
 * @param bool $selectDb Whether to select the specific database or connect to MySQL server only
 * @return PDO
 */
function getDBConnection($selectDb = true) {
    try {
        if ($selectDb) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        }
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If connecting to taskflow_db fails and we are not in installer, provide a friendly message
        $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
        if ($selectDb && $e->getCode() == 1049 && $currentScript !== 'install.php') {
            header('Location: install.php?reason=no_db');
            exit;
        }
        throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
    }
}
