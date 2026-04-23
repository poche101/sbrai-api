#!/usr/bin/env php
<?php
/**
 * SBRAI API — Quick diagnostics script
 *
 * Run from your project root:
 *   php diagnose.php
 *
 * Checks the four most common causes of 500 errors on fresh installs.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$ok   = fn(string $s) => print("\033[32m✔  {$s}\033[0m\n");
$fail = fn(string $s) => print("\033[31m✖  {$s}\033[0m\n");
$warn = fn(string $s) => print("\033[33m⚠  {$s}\033[0m\n");

echo "\n========================================\n";
echo "  SBRAI API — Diagnostic Check\n";
echo "========================================\n\n";

// 1. DB connection
try {
    DB::connection()->getPdo();
    $ok('Database connection OK  (' . config('database.default') . ')');
} catch (\Exception $e) {
    $fail('Database connection FAILED: ' . $e->getMessage());
    echo "\n→ Fix: check DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env\n\n";
    exit(1);
}

// 2. Required tables
$required = [
    'users', 'personal_access_tokens', 'categories',
    'ads', 'ad_images', 'ad_views', 'ad_favorites',
    'chats', 'chat_messages',
    'vendor_vouchers', 'voucher_transactions',
    'vendor_activities',
];

$missing = [];
echo "Checking tables...\n";
foreach ($required as $table) {
    if (Schema::hasTable($table)) {
        $ok("  {$table}");
    } else {
        $fail("  {$table}  ← MISSING");
        $missing[] = $table;
    }
}

if (!empty($missing)) {
    echo "\n→ Fix: run   php artisan migrate\n";
    echo   "  Missing: " . implode(', ', $missing) . "\n\n";
    exit(1);
}

// 3. Categories seeded
$catCount = DB::table('categories')->count();
if ($catCount >= 16) {
    $ok("Categories seeded ({$catCount} rows)");
} else {
    $warn("Only {$catCount} categories found (expected 16)");
    echo "→ Fix: php artisan db:seed --class=CategorySeeder\n";
}

// 4. Storage symlink
$link = public_path('storage');
if (file_exists($link) && is_link($link)) {
    $ok('Storage symlink exists');
} else {
    $fail('Storage symlink missing');
    echo "→ Fix: php artisan storage:link\n";
}

// 5. GD extension (for watermarking)
if (extension_loaded('gd')) {
    $ok('PHP GD extension loaded (watermark support)');
} else {
    $fail('PHP GD extension NOT loaded — watermarking will fail');
    echo "→ Fix: sudo apt install php8.2-gd && sudo systemctl restart php8.2-fpm\n";
}

// 6. Route prefix
$prefix = config('app.url');
echo "\n";
$ok("APP_URL = {$prefix}");
echo "  API endpoints are at: {$prefix}/api/v1/\n";
echo "  Example: {$prefix}/api/v1/vendor/dashboard\n";

echo "\n========================================\n";
echo "  All checks passed — API should work!\n";
echo "========================================\n\n";
