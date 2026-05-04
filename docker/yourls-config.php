<?php
/**
 * YOURLS configuration for the Docker dev stack.
 * This file is ONLY used inside the docker-compose setup.
 * It is mounted read-only at /var/www/html/user/config.php.
 */

define( 'YOURLS_DB_USER',   'yourls' );
define( 'YOURLS_DB_PASS',   'yourlspw' );
define( 'YOURLS_DB_NAME',   'yourls' );
define( 'YOURLS_DB_HOST',   'db' );
define( 'YOURLS_DB_PREFIX', 'yourls_' );

define( 'YOURLS_SITE', 'http://localhost:8080' );
define( 'YOURLS_HOURS_OFFSET', 0 );
define( 'YOURLS_LANG', '' );

define( 'YOURLS_UNIQUE_URLS', true );

// Private admin (login required) — public shortlinks still resolve.
define( 'YOURLS_PRIVATE', true );

// Dev cookie key — DO NOT REUSE IN PRODUCTION.
define( 'YOURLS_COOKIEKEY', 'docker-dev-cookie-key-not-secure-change-me' );

// Local credentials. Plain-text on first login; YOURLS will hash them in-place,
// but since this file is mounted read-only that hashing won't persist — use the
// same password each time, or remount RW if you want auto-hashing.
$yourls_user_passwords = array(
    'admin' => 'admin',
);

define( 'YOURLS_DEBUG', true );
define( 'YOURLS_URL_CONVERT', 36 );

$yourls_reserved_URL = array();
