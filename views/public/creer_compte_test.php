<?php
require '../../config/database.php';
$hash = password_hash("Pierre123", PASSWORD_DEFAULT);
$user = $db->prepare("INSERT INTO utilisateurs (username, password, role) VALUES (:username, :password, :role)");
$user->execute([':username' => 'Pierre', ':password' => $hash, ':role' => 'super_admin']);