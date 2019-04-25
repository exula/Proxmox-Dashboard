<?php
/*
 * You can either add your details directly in this file,
 * or the safe way is to use an environment file.
 *
 * Read more:
 * http://laravel.com/docs/configuration#protecting-sensitive-configuration
 */


//We need to determine which hosts are 'up'
if(!empty(getenv('PROXMOX_HOST'))) {
    $hosts = explode(',', getenv('PROXMOX_HOST'));
} else {
    $hosts = [];
}
$workingHost = '';

foreach ($hosts as $host) {
    $port = getenv('PROXMOX_PORT') ?: 8006;
    $waitTimeoutInSeconds = 1;
    if ($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds)) {
        // It worked
        $workingHost = $host;
    }
    fclose($fp);
}



return [
    'server' => [
        'hostname' => $workingHost,
        'username' => getenv('PROXMOX_USER'),
        'password' => getenv('PROXMOX_PASS'),
        // sensible defaults for these two
        'realm'    => getenv('PROXMOX_REALM') ?: 'pam',
        'port'     => getenv('PROXMOX_PORT') ?: 8006,
    ]
];