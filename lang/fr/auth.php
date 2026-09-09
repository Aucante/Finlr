<?php

/*
|--------------------------------------------------------------------------
| Authentication Language Lines (French)
|--------------------------------------------------------------------------
|
| Partial override: only the key introduced for OAuth login is translated
| here. Every other key falls back to lang/en/auth.php, unchanged from
| before this file existed.
|
*/

return [

    'oauth_failed' => 'La connexion avec ce fournisseur a échoué. Merci de réessayer.',
    'oauth_account_exists' => 'Un compte existe déjà avec cette adresse e-mail. Connectez-vous d\'abord à ce compte, puis retentez la liaison avec ce fournisseur.',

    'two_factor' => [
        'mail' => [
            'subject' => 'Votre code de connexion',
            'greeting' => 'Bonjour !',
            'intro' => 'Voici le code à saisir pour finaliser votre connexion.',
            'code_line' => 'Code de vérification : :code',
            'expiry_notice' => 'Ce code expire dans :minutes minutes. Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet e-mail.',
        ],
        'challenge' => [
            'invalid_code' => 'Ce code est incorrect ou a expiré.',
            'session_expired' => 'Votre tentative de connexion a expiré. Merci de vous reconnecter.',
        ],
    ],

];
