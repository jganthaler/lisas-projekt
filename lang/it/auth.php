<?php

/*
 | Signing in, registering, the password reset and the reminder that the
 | e-mail address still has to be confirmed.
 |
 | "title" is the name of the browser tab, "heading" the headline of the page.
 | The hint on the verification page is built around the address of the account
 | in the view, which is why it is split into "sent_before" and "sent_after".
 */

return [
    'tagline' => 'Tenete tutto sotto controllo insieme.',

    'login' => [
        'title' => 'Accedi',
        'heading' => 'Bentornato',
        'subtitle' => 'Accedi per aprire il tuo congelatore.',
        'remember' => 'Resta connesso',
        'submit' => 'Accedi',
        'failed' => 'L\'e-mail o la password non corrispondono.',
        'throttled' => 'Troppi tentativi di accesso. Riprova tra un minuto.',
        'no_account' => 'Non hai ancora un account?',
        'register' => 'Registrati ora',
    ],

    'register' => [
        'title' => 'Registrati',
        'heading' => 'Crea un account',
        'subtitle' => 'Il tuo congelatore con tre cassetti ti sta già aspettando.',
        'submit' => 'Registrati',
        'busy' => 'Creazione dell\'account …',
        'have_account' => 'Già registrato?',
        'login' => 'Vai all\'accesso',
        'status' => 'Ci siamo quasi! Ti abbiamo inviato un link a :email.',

        'consent' => 'Registrandoti accetti le :terms e l’:privacy.',
    ],

    'forgot' => [
        'title' => 'Password dimenticata',
        'heading' => 'Password dimenticata?',
        'subtitle' => 'Nessun problema: inserisci il tuo indirizzo e-mail e ti invieremo un link per reimpostarla. Resta valido per un\'ora.',
        'submit' => 'Invia il link',
        'sent' => 'Se esiste un account per :email, l\'e-mail con il link è in viaggio. Controlla anche la cartella spam.',
        'hint' => 'Non è arrivato nulla? Aspetta un momento e richiedi di nuovo il link.',
        'back_to_login' => 'Torna all\'accesso',
        'throttled' => 'Troppe richieste. Riprova tra un minuto.',
        'already_requested' => 'È stato appena richiesto un link. Attendi un minuto.',
    ],

    'reset' => [
        'title' => 'Nuova password',
        'subtitle' => 'Scegli una nuova password per il tuo account. Subito dopo potrai accedere con essa.',
        'submit' => 'Salva la password',
        'invalid_link' => 'Il link non è valido o è scaduto. Richiedine uno nuovo.',
        'status' => 'La tua password è stata modificata. Accedi di nuovo.',
        'expired_question' => 'Link scaduto?',
        'request_new' => 'Richiedine uno nuovo',
    ],

    'verify' => [
        'title' => 'Conferma l\'e-mail',
        'heading' => 'Conferma l\'indirizzo e-mail',
        'sent_before' => 'Ti abbiamo inviato un link a',
        'sent_after' => 'Fai clic sul link per attivare il tuo congelatore.',
        'resend' => 'Invia di nuovo il link',
        'throttled' => 'Attendi un minuto prima di richiedere un nuovo link.',
        'sent' => 'Ti abbiamo inviato un nuovo link a :email.',
        'confirmed' => 'Il tuo indirizzo e-mail è confermato. Benvenuto!',
    ],
];
