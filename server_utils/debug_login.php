<?php
/**
 * Simple Login Debug - No Laravel Bootstrap
 * Direct database connection only
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$dbHost = 'localhost';
$dbName = 'lbccompa_shoppymax';
$dbUser = 'lbccompa_shoppymax_admin';
$dbPass = '5S6PO4wb~(OR!A12';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Debug - Simple</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; }
        .success { color: #155724; background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #28a745; }
        .error { color: #721c24; background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #dc3545; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #ffc107; }
        .info { color: #004085; background: #cce5ff; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #007bff; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td { padding: 10px; border: 1px solid #ddd; }
        table td:first-child { font-weight: bold; background: #f8f9fa; width: 250px; }
        h3 { margin-top: 30px; padding: 10px; background: #343a40; color: white; border-radius: 4px; }
        .btn { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0; }
        .btn:hover { background: #218838; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 ShoppyMax Login Debug (Simple Mode)</h1>
    <p>Testing authentication without Laravel bootstrap...</p>

<?php
try {
    // Connect to database
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    $email = 'admin@shoppy-max.com';
    $testPassword = 'password';
    
    // Find user
    echo "<h3>1️⃣ User Database Check</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<div class='error'>❌ User NOT FOUND with email: {$email}</div>";
        echo "<p>Checking all users in database...</p>";
        $allUsers = $pdo->query("SELECT id, name, email, user_type FROM users")->fetchAll();
        echo "<pre>" . print_r($allUsers, true) . "</pre>";
        exit;
    }
    
    echo "<div class='success'>✅ User found in database</div>";
    echo "<table>";
    echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>Name</td><td>{$user['name']}</td></tr>";
    echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>User Type</td><td>{$user['user_type']}</td></tr>";
    echo "<tr><td>Email Verified</td><td>" . ($user['email_verified_at'] ? '✅ Yes - ' . $user['email_verified_at'] : '❌ No') . "</td></tr>";
    echo "<tr><td>Created At</td><td>{$user['created_at']}</td></tr>";
    echo "<tr><td>Password Hash (first 60 chars)</td><td><code>" . substr($user['password'], 0, 60) . "</code></td></tr>";
    echo "<tr><td>Hash Length</td><td>" . strlen($user['password']) . " characters</td></tr>";
    echo "<tr><td>Hash Type</td><td>" . (strpos($user['password'], '$2y$') === 0 ? '✅ Bcrypt' : '⚠️ Unknown') . "</td></tr>";
    echo "</table>";
    
    // Check roles
    echo "<h3>2️⃣ Roles Check</h3>";
    $stmt = $pdo->prepare("
        SELECT r.name 
        FROM roles r 
        INNER JOIN model_has_roles mhr ON r.id = mhr.role_id 
        WHERE mhr.model_id = ? AND mhr.model_type = 'App\\\\Models\\\\User'
    ");
    $stmt->execute([$user['id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($roles) > 0) {
        echo "<div class='success'>✅ User has roles: " . implode(', ', $roles) . "</div>";
    } else {
        echo "<div class='warning'>⚠️ User has NO roles assigned!</div>";
    }
    
    // Test password
    echo "<h3>3️⃣ Password Hash Test</h3>";
    echo "<div class='info'>Testing password: <code>{$testPassword}</code> against stored hash</div>";
    
    $passwordVerified = password_verify($testPassword, $user['password']);
    
    if ($passwordVerified) {
        echo "<div class='success'>";
        echo "<h4>✅ PASSWORD VERIFICATION SUCCESSFUL!</h4>";
        echo "<p>The password '<strong>{$testPassword}</strong>' matches the hash in the database.</p>";
        echo "<p>This means the issue is NOT with the password hash.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ PASSWORD VERIFICATION FAILED!</h4>";
        echo "<p>The password '<strong>{$testPassword}</strong>' does NOT match the hash in database.</p>";
        echo "<p><strong>This is the problem!</strong> The password hash needs to be regenerated.</p>";
        echo "</div>";
        
        // Show fix option
        echo "<div class='warning'>";
        echo "<h4>🔧 Fix Available</h4>";
        
        if (isset($_GET['fix'])) {
            // Generate new hash
            $newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
            
            echo "<div class='success'>";
            echo "<h4>✅ PASSWORD FIXED!</h4>";
            echo "<p>New password hash has been generated and saved.</p>";
            echo "<p>New hash: <code>" . substr($newHash, 0, 60) . "...</code></p>";
            echo "<p><strong>Now try logging in!</strong></p>";
            echo "<p><a href='/login' style='display:inline-block; background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Go to Login Page</a></p>";
            echo "</div>";
            
            // Verify the fix
            $verifyNew = password_verify($testPassword, $newHash);
            echo "<p>Verification test: " . ($verifyNew ? '✅ New hash works!' : '❌ Something went wrong') . "</p>";
            
        } else {
            echo "<p>Click the button below to automatically fix the password hash:</p>";
            echo "<a href='?fix=1' class='btn'>🔧 Fix Password Hash Now</a>";
        }
        echo "</div>";
    }
    
    // Additional checks
    echo "<h3>4️⃣ Additional Information</h3>";
    
    echo "<table>";
    echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
    echo "<tr><td>Password Hashing Available</td><td>" . (function_exists('password_hash') ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>Bcrypt Available</td><td>" . (defined('PASSWORD_BCRYPT') ? '✅ Yes' : '❌ No') . "</td></tr>";
    
    // Test hash generation
    $testHash = password_hash('test123', PASSWORD_BCRYPT);
    $testVerify = password_verify('test123', $testHash);
    echo "<tr><td>Test Hash/Verify</td><td>" . ($testVerify ? '✅ Working' : '❌ Not working') . "</td></tr>";
    echo "</table>";
    
    // Summary
    echo "<h3>5️⃣ Summary & Next Steps</h3>";
    
    if ($passwordVerified) {
        echo "<div class='success'>";
        echo "<h4>✅ DIAGNOSIS: Password is Correct!</h4>";
        echo "<p>The password hash is working correctly. The login issue might be caused by:</p>";
        echo "<ul>";
        echo "<li>Session configuration issues</li>";
        echo "<li>CSRF token problems</li>";
        echo "<li>Laravel authentication guard misconfiguration</li>";
        echo "<li>Cache issues - try clearing Laravel cache</li>";
        echo "</ul>";
        echo "<p><strong>Try these solutions:</strong></p>";
        echo "<ol>";
        echo "<li>Clear browser cookies and cache</li>";
        echo "<li>Try in incognito/private window</li>";
        echo "<li>Check Laravel logs in <code>storage/logs/laravel.log</code></li>";
        echo "<li>Run: <code>php artisan cache:clear</code> and <code>php artisan config:clear</code></li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ DIAGNOSIS: Password Hash is Incorrect!</h4>";
        echo "<p><strong>Action Required:</strong> Click the 'Fix Password Hash Now' button above.</p>";
        echo "</div>";
    }
    
    echo "<div class='warning' style='margin-top: 30px; padding: 20px;'>";
    echo "<h4>⚠️ SECURITY REMINDER</h4>";
    echo "<p><strong>DELETE this file (debug_login_simple.php) from your server immediately after fixing!</strong></p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>📋 Login Details</h4>";
    echo "<p><strong>Login URL:</strong> <a href='/login'>https://shoppymax.codezela.com/login</a></p>";
    echo "<p><strong>Email:</strong> <code>admin@shoppy-max.com</code></p>";
    echo "<p><strong>Password:</strong> <code>password</code></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check the database credentials at the top of this file.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

</div>
</body>
</html>
