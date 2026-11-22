<?php
/**
 * Unit tests run without WordPress. Only the constants that class files read at load time are defined here.
 *
 * @package MultisiteGuardian
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

require dirname( __DIR__ ) . '/vendor/autoload.php';
