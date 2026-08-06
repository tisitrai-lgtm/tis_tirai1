<?php
// ============================================================
// yes_Chef — config/google_oauth.php
// ============================================================

define('GOOGLE_CLIENT_ID',     '1055747509682-ergil55r71an9rmfsde126aqgcp9stav.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI',  'http://localhost/givewater/yes_chef/auth/google_callback.php');

define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO',  'https://www.googleapis.com/oauth2/v3/userinfo');

define('APP_URL', 'http://localhost/givewater/yes_chef');
define('APP_NAME', 'yes_Chef');
