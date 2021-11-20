<?php

$settings = [
    'db_driver' => getenv('CHEVERETO_DB_DRIVER'),
    'db_host' => getenv('CHEVERETO_DB_HOST'),
    'db_name' => getenv('CHEVERETO_DB_NAME'),
    'db_pass' => getenv('CHEVERETO_DB_PASS'),
    'db_pdo_attrs' => json_decode(getenv('CHEVERETO_DB_PDO_ATTRS'), true),
    'db_port' => (int) getenv('CHEVERETO_DB_PORT'),
    'db_table_prefix' => getenv('CHEVERETO_DB_TABLE_PREFIX'),
    'db_user' => getenv('CHEVERETO_DB_USER'),
    'debug_level' => (int) getenv('CHEVERETO_DEBUG_LEVEL'),
    'disable_php_pages' => (bool) getenv('CHEVERETO_DISABLE_PHP_PAGES'),
    'disable_update_http' => (bool) getenv('CHEVERETO_DISABLE_UPDATE_HTTP'),
    'disable_update_cli' => (bool) getenv('CHEVERETO_DISABLE_UPDATE_CLI'),
    'error_log' => getenv('CHEVERETO_ERROR_LOG'),
    'hostname_path' => getenv('CHEVERETO_HOSTNAME_PATH'),
    'hostname' => getenv('CHEVERETO_HOSTNAME'),
    'https' => (bool) getenv('CHEVERETO_HTTPS'),
    'image_formats_available' => json_decode(getenv('CHEVERETO_IMAGE_FORMATS_AVAILABLE'), true),
    'image_library' => getenv('CHEVERETO_IMAGE_LIBRARY'),
    'session.save_handler' => getenv('CHEVERETO_SESSION_SAVE_HANDLER'),
    'session.save_path' => getenv('CHEVERETO_SESSION_SAVE_PATH'),
];