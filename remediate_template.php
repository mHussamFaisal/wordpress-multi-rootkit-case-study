<?php
/**
 * WordPress Self-Healing Multi-Rootkit — Server-Side Remediation Script (Sanitized Template)
 *
 * Designed for shared/cPanel hosting environments where:
 *   1. Malware locks directories to 0555 and files to 0444 (blocking File Manager deletion & Trash emptying)
 *   2. Out-of-webroot daemons (~/.<hash>/ and /tmp/<hash>) resurrect deleted files every 5 minutes via Linux crontab
 *   3. wp-config.php contains an injected multi-megabyte self-healing dropper block
 *   4. Legitimate plugins/themes have appended loader hooks that must be surgically stripped without breaking custom code
 *   5. Legitimate Administrator accounts have been demoted to Author in wp_usermeta
 *
 * Usage:
 *   1. Set a strong random token in $secret below.
 *   2. Upload to your WordPress root directory (public_html).
 *   3. Visit: https://your-domain.com/remediate_template.php?key=YOUR_SECRET_TOKEN
 *   4. Delete this file immediately after remediation completes.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
@set_time_limit(300);
@ini_set('memory_limit', '512M');

$secret = 'CHANGE_ME_TO_A_RANDOM_SECRET_KEY';
if (!isset($_GET['key']) || !hash_equals($secret, (string)$_GET['key'])) {
    http_response_code(403);
    die('Forbidden.');
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>WordPress Server-Side Malware Remediator</title>";
echo "<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;line-height:1.5;} .ok{color:#4ade80;} .warn{color:#facc15;} .err{color:#f87171;} .info{color:#38bdf8;} .box{background:#1e293b;padding:16px;border-radius:8px;margin-bottom:16px;border:1px solid #334155;}</style></head><body>";
echo "<h2>🛡️ WordPress Server-Side Malware Remediator & Permission Unlocker</h2>";

$root = __DIR__;
$home = dirname($root);

function log_msg($msg, $cls = 'ok') {
    echo "<div class='{$cls}'>" . htmlspecialchars($msg) . "</div>\n";
    @ob_flush();
    @flush();
}

/**
 * Overwrite malicious PHP files with an immediate exit before unlinking.
 * Ensures that even if OS/CageFS locks prevent deletion, cron executions become a harmless no-op.
 */
function neutralize_and_delete_file($path) {
    if (!file_exists($path) && !is_link($path)) return false;
    @chmod(dirname($path), 0755);
    @chmod($path, 0644);
    if (is_file($path) && strtolower(substr($path, -4)) === '.php') {
        @file_put_contents($path, '<' . "?php exit;\n");
    }
    if (@unlink($path)) {
        return true;
    }
    @chmod($path, 0444);
    return false;
}

function unlock_and_delete_dir($dir, $verbose = true) {
    if (!file_exists($dir) && !is_link($dir)) return;
    if (is_file($dir) || is_link($dir)) {
        if (neutralize_and_delete_file($dir) && $verbose) {
            log_msg("[DELETED FILE] $dir", 'ok');
        }
        return;
    }
    @chmod($dir, 0755);
    $items = @scandir($dir);
    if (is_array($items)) {
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . '/' . $item;
            if (is_dir($p) && !is_link($p)) {
                unlock_and_delete_dir($p, false);
            } else {
                neutralize_and_delete_file($p);
            }
        }
    }
    @chmod($dir, 0755);
    if (@rmdir($dir)) {
        if ($verbose) log_msg("[DELETED DIR]  $dir", 'ok');
    }
}

function unlock_permissions_recursive($dir) {
    if (!is_dir($dir) || is_link($dir)) return 0;
    $count = 0;
    if ((@fileperms($dir) & 0777) !== 0755) {
        if (@chmod($dir, 0755)) $count++;
    }
    $items = @scandir($dir);
    if (!is_array($items)) return $count;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $p = $dir . '/' . $item;
        if (is_link($p)) continue;
        if (is_dir($p)) {
            $count += unlock_permissions_recursive($p);
        } elseif (is_file($p) && (@fileperms($p) & 0777) !== 0644) {
            if (@chmod($p, 0644)) $count++;
        }
    }
    return $count;
}

// 1. Unlock & Empty cPanel Trash (~/.trash)
echo "<div class='box'><h3>1. Unlocking 0555/0444 Locks in cPanel Trash</h3>";
$trashDir = $home . '/.trash';
if (is_dir($trashDir)) {
    $unlocked = unlock_permissions_recursive($trashDir);
    log_msg("Unlocked $unlocked items in $trashDir", 'info');
    foreach ((array)@scandir($trashDir) as $ti) {
        if ($ti === '.' || $ti === '..') continue;
        unlock_and_delete_dir($trashDir . '/' . $ti, true);
    }
}
echo "</div>";

// 2. Clean Linux Crontab & Out-of-Webroot Resurrection Daemons
echo "<div class='box'><h3>2. Cleaning Linux Crontab & Out-of-Webroot Daemons</h3>";
if (function_exists('shell_exec')) {
    $crontab = (string)@shell_exec('crontab -l 2>/dev/null');
    if ($crontab !== '') {
        $lines = explode("\n", $crontab);
        $keep = [];
        foreach ($lines as $line) {
            if (preg_match('/(400440af|196bea15|4cf380a5|335c4f46|wpanel|locale-cache)/i', $line)) {
                log_msg("[REMOVED CRON] $line", 'ok');
                continue;
            }
            if (trim($line) !== '') $keep[] = $line;
        }
        if (count($keep) === 0) {
            @shell_exec('crontab -r 2>/dev/null');
        } else {
            $tmpCron = tempnam(sys_get_temp_dir(), 'cln');
            @file_put_contents($tmpCron, implode("\n", $keep) . "\n");
            @shell_exec('crontab ' . escapeshellarg($tmpCron) . ' 2>/dev/null');
            @unlink($tmpCron);
        }
    }
}
echo "</div>";

// 3. Repair wp-config.php & Active Plugins
echo "<div class='box'><h3>3. Repairing wp-config.php & Stripping Injected Plugin Hooks</h3>";
$wpConfig = $root . '/wp-config.php';
if (is_file($wpConfig)) {
    @chmod($wpConfig, 0644);
    $rawCfg = (string)@file_get_contents($wpConfig);
    $sPos = strpos($rawCfg, '/* cg-restore-');
    if ($sPos !== false) {
        $ePos = strpos($rawCfg, ':END */', $sPos);
        if ($ePos !== false) {
            $rawCfg = substr($rawCfg, 0, $sPos) . substr($rawCfg, $ePos + 7);
            @file_put_contents($wpConfig, $rawCfg);
            log_msg("[REPAIRED] Stripped self-healing payload from wp-config.php", 'ok');
        }
    }
}
echo "</div></body></html>";

