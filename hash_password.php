<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

$factory = new PasswordHasherFactory([
    'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => ['algorithm' => 'auto'],
]);

$hasher = $factory->getPasswordHasher('Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface');

$adminHash = $hasher->hash('admin');
$userHash = $hasher->hash('password');

echo "Admin password hash: " . $adminHash . "\n";
echo "User password hash: " . $userHash . "\n";
