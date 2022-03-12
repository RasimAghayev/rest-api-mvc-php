<?php

$token_payload = [
    'iss' => 'https://github.com/auth0/php-jwt-example',
    'sub' => '123456',
    'name' => 'John Doe',
    'email' => 'info@auth0.com'
];
$key = '__test_secret__';
$jwt = JWT::encode($token_payload, base64_decode(strtr($key, '-_', '+/')), 'HS256');
print "JWT:\n";
echo json_encode($jwt);
$decoded = JWT::decode($jwt, base64_decode(strtr($key, '-_', '+/')), ['HS256']);
print "\n\n";
print "Decoded:\n";
echo json_encode($decoded);