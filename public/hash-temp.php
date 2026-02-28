<?php
// Temporary file - DELETE after use!
$password = 'Tkk123@';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h3>Password Hash Generator</h3>";
echo "<p><strong>Password:</strong> {$password}</p>";
echo "<p><strong>Hash:</strong> {$hash}</p>";
echo "<hr>";
echo "<p><strong>SQL Command:</strong></p>";
echo "<pre>UPDATE `admin` SET `password` = '{$hash}' WHERE `username` = 'admin';</pre>";
echo "<hr><p style='color:red;'>⚠️ DELETE this file after use!</p>";
