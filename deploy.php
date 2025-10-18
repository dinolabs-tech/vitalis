<?php
/**
 * Auto Deployment Script for Edupack
 * ----------------------------------
 * Triggered by a GitHub webhook on each push event.
 * Automatically runs `git pull` in the /edupack folder.
 * 
 * Secure key: foxtrot2november
 */

$secret = "foxtrot2november"; // must match the key in your webhook URL
$path   = "/home/dinolabs/public_html/vitalis";

// --- Security check ---
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    die('❌ Unauthorized access');
}

// --- Run git pull command ---
$output = shell_exec("cd $path && git pull 2>&1");

// --- Log the deployment result ---
$logFile = $path . "/deploy.log";
file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "]\n" . $output . "\n\n", FILE_APPEND);

// --- Display output for debugging ---
echo "<h3>✅ Deployment complete</h3>";
echo "<pre>$output</pre>";
?>
