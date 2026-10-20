<?php

/*
 | The profile of the signed in user - including the language of the app, which
 | is picked here (see App\Support\Locales).
 */

return [
    'title' => 'Profilo',
    'subtitle' => 'I tuoi dati e la tua immagine del profilo',
    'avatar' => 'Immagine del profilo',
    'avatar_preview' => 'Anteprima',
    'choose_image' => 'Scegli un\'immagine',
    'uploading' => 'Caricamento …',
    'remove_avatar_title' => 'Rimuovere l\'immagine del profilo?',
    'remove_avatar_message' => 'La tua immagine viene eliminata – dopo torneranno visibili le tue iniziali.',
    'details' => 'Dati',
    // L'indirizzo e-mail confermato è il nome dell'account: il profilo lo mostra
    // soltanto, non c'è più un campo per modificarlo (vedi
    // App\Livewire\Profile\ProfilePage::updateProfile()).
    'email_locked' => 'Il tuo indirizzo è confermato e non può quindi essere modificato.',
    'password_heading' => 'Cambia la password',
    'current_password' => 'Password attuale',
    'new_password' => 'Nuova password',
    'password_confirmation' => 'Ripeti la nuova password',
    'language_heading' => 'Lingua',
    'language_saved' => 'Lingua modificata.',

    // L'app installata (PWA): la sezione sta subito sotto la lingua e compare
    // solo quando il browser offre l'installazione o un iPhone ha bisogno del
    // menu di condivisione (resources/js/app.js).
    'install_heading' => 'Installa l\'app',
    'install_ios' => 'Tocca «Condividi» in basso, poi «Aggiungi alla schermata Home».',
    'install_button' => 'Aggiungi alla schermata Home',
    'saved' => 'Profilo salvato.',
    'password_changed' => 'Password modificata.',
    'image_saved' => 'Immagine del profilo salvata.',
    'image_removed' => 'Immagine del profilo rimossa.',
    'image_failed' => 'Non è stato possibile salvare l\'immagine.',
    'image_invalid' => 'Scegli un\'immagine in formato JPG, PNG o WebP.',
    'image_too_large' => 'L\'immagine supera i 10 MB. Scegline una più piccola.',

    // Note legali: le pagine delle note legali, dell’informativa sulla privacy e
    // delle condizioni d’uso (resources/views/legal/*) e la cancellazione
    // dell’account, che attua l’art. 17 GDPR.
    'legal_heading' => 'Note legali',
    'delete_heading' => 'Cancella account',
    'delete_hint' => 'Rimuove il tuo account, l’immagine del profilo e tutti i congelatori che hai creato con il loro contenuto. Non è reversibile.',
    'delete_password' => 'Conferma la password',
    'delete_button' => 'Cancella definitivamente l’account',
    'delete_confirm_title' => 'Cancellare davvero l’account?',
    'delete_confirm_message' => 'Vengono cancellati l’account e tutti i dati che contiene – anche i tuoi congelatori con il loro contenuto. I congelatori che altri hanno condiviso con te restano. Una cancellazione non è reversibile.',
    'account_deleted' => 'Il tuo account è stato cancellato.',
];
