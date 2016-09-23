<?php
/*
 * You can either add your details directly in this file,
 * or the safe way is to use an environment file.
 *
 * Read more:
 * http://laravel.com/docs/configuration#protecting-sensitive-configuration
 */

return [
    'server' => [
        'hostname' => getenv('PROXMOX_HOST'),
        'username' => getenv('PROXMOX_USER'),
        'password' => getenv('PROXMOX_PASS'),
        // sensible defaults for these two
        'realm'    => getenv('PROXMOX_REALM') ?: 'pam',
        'port'     => getenv('PROXMOX_PORT') ?: 8006,
    ]
];