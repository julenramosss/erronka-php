<?php
return [
    'base_url' => getenv('API_BASE_URL') ?: 'http://10.23.26.64:3000',
    'change_pwd_path' => getenv('API_CHANGE_PWD_PATH') ?: '/api/auth/changePwd',
    'login_url' => getenv('LOGIN_URL') ?: '/login',
    'support_email' => getenv('SUPPORT_EMAIL') ?: 'soporte@pakag.com',
    'request_timeout_seconds' => (int) (getenv('API_TIMEOUT_SECONDS') ?: 10),
];
