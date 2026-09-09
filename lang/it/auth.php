<?php

/*
|--------------------------------------------------------------------------
| Authentication Language Lines (Italian)
|--------------------------------------------------------------------------
|
| Partial override: only the key introduced for OAuth login is translated
| here. Every other key falls back to lang/en/auth.php, unchanged from
| before this file existed.
|
*/

return [

    'oauth_failed' => 'Accesso con questo provider non riuscito. Riprova.',
    'oauth_account_exists' => 'Esiste già un account con questo indirizzo email. Accedi prima a quell\'account, poi riprova a collegare questo provider.',

    'two_factor' => [
        'mail' => [
            'subject' => 'Il tuo codice di accesso',
            'greeting' => 'Ciao!',
            'intro' => 'Ecco il codice per completare l\'accesso.',
            'code_line' => 'Codice di verifica: :code',
            'expiry_notice' => 'Questo codice scade tra :minutes minuti. Se non hai richiesto tu questo codice, puoi ignorare questa email.',
        ],
    ],

];
