<?php
// Router untuk PHP built-in server (Railway deployment)
// Menggantikan fungsi .htaccess pada Apache

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Jika file static (css, js, gambar, dll) — serve langsung
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    if (is_dir(__DIR__ . $uri)) {
        $index_file = rtrim(__DIR__ . $uri, '/') . '/index.php';
        if (file_exists($index_file)) {
            require_once $index_file;
            return true;
        }
    }
    return false;
}

// Default: serve index.php (redirect ke login)
require_once __DIR__ . '/index.php';
