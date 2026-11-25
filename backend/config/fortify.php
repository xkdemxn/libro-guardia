<?php

use Laravel\Fortify\Features;

return [
    'username' => 'indicativo',

    'features' => [
        Features::twoFactorAuthentication(['confirm' => true]),
    ],
];
