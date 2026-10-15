<?php

/*
 | Le pagine legali dell’applicazione: note legali, informativa sulla privacy e
 | condizioni d’uso.
 |
 | I testi descrivono che cosa fa realmente questa applicazione: quali dati
 | vengono memorizzati, dove si trovano, quali servizi sono coinvolti e quale
 | cookie viene impostato. I dati del fornitore (e di hosting e posta) non
 | stanno qui ma in config/legal.php, che li legge dall’ambiente (LEGAL_*); i
 | segnaposto ":operator", ":hosting", ":mail_provider" e ":date" vengono
 | sostituiti da App\Support\Legal al momento del rendering.
 |
 | Ogni documento è fatto di "sections": un titolo, alcuni paragrafi e
 | facoltativamente un elenco. Così la struttura resta uguale nelle tre lingue
 | (resources/views/legal/*). I file tedeschi sono la fonte di verità.
 |
 | Questi testi sono modelli: coprono il caso abituale di un’applicazione
 | privata e gratuita per la famiglia e devono comunque essere verificati dal
 | fornitore (vedi il README, sezione "Rechtliches").
 */

return [
    'imprint' => [
        'title' => 'Note legali',
        'heading' => 'Note legali',
        'intro' => 'Chi fornisce questa applicazione e come contattarlo.',
        'provider_heading' => 'Informazioni ai sensi del § 5 DDG',
        'provider_note' => ':app è un servizio privato e gratuito per gestire insieme i congelatori in famiglia. Non vengono venduti beni né servizi.',
        'contact_heading' => 'Contatti',
        'email_label' => 'E-mail',
        'phone_label' => 'Telefono',
        'incomplete_heading' => 'Mancano i dati del fornitore',
        'incomplete_body' => 'Questa pagina non è ancora completa: nella configurazione non sono indicati i dati del fornitore. Fino ad allora all’applicazione mancano le note legali richieste dalla legge. Vanno compilati i valori LEGAL_* di .env.example (vedi config/legal.php).',
        'missing_heading' => 'Mancano:',
        'sections' => [
            [
                'heading' => 'Responsabilità per i contenuti',
                'body' => [
                    'I contenuti di questa applicazione nascono da ciò che inseriscono le persone che la usano: nomi, quantità e date di scadenza degli alimenti sono gestiti da loro. Non si fornisce alcuna garanzia sulla correttezza, completezza e attualità di queste indicazioni.',
                    'In quanto fornitore di servizi non siamo obbligati a sorvegliare informazioni trasmesse o memorizzate di terzi né a ricercare circostanze che indichino un’attività illecita. Restano impregiudicati gli obblighi di rimuovere o bloccare informazioni previsti dalle leggi generali.',
                ],
            ],
            [
                'heading' => 'Responsabilità per i link',
                'body' => [
                    'Questa applicazione non contiene link a contenuti di terzi. Se le persone che la usano rimandano a offerte altrui, ne rispondono esclusivamente i loro gestori.',
                ],
            ],
            [
                'heading' => 'Composizione delle controversie con i consumatori',
                'body' => [
                    'Non siamo obbligati né disposti a partecipare a procedure di composizione delle controversie davanti a un organismo di conciliazione per i consumatori.',
                ],
            ],
            [
                'heading' => 'Ambito di applicazione',
                'body' => [
                    'Queste note legali valgono per :app su :url.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy',
        'heading' => 'Informativa sulla privacy',
        'intro' => 'Quali dati memorizza :app, perché, per quanto tempo – e quali diritti hai.',
        'controller_heading' => 'Titolare',
        'controller_intro' => 'Il titolare del trattamento dei dati personali in questa applicazione è:',
        'sections' => [
            [
                'heading' => 'In breve',
                'body' => [
                    ':app è un servizio privato per la propria famiglia. L’applicazione rinuncia a pubblicità, strumenti di analisi e profilazione.',
                ],
                'list' => [
                    'Vengono memorizzati solo i dati necessari al funzionamento: il tuo account, il contenuto dei tuoi congelatori e i dati tecnici della sessione.',
                    'I dati non vengono venduti né trasmessi per scopi pubblicitari.',
                    'Oltre al cookie tecnicamente necessario per l’accesso, nel browser viene salvata solo la tua scelta del colore (chiaro o scuro): per questo qui non c’è alcun banner per i cookie.',
                    'Puoi cancellare il tuo account in qualsiasi momento dal profilo.',
                ],
            ],
            [
                'heading' => 'Account e accesso',
                'body' => [
                    'Per ogni account memorizziamo un nome visualizzato (facoltativo; senza indicazione viene mostrata la parte prima della @ del tuo indirizzo e-mail), il tuo indirizzo e-mail, la tua password (solo come hash, quindi non leggibile), la lingua che hai scelto, un’immagine del profilo caricata facoltativamente e il momento della conferma dell’indirizzo e-mail.',
                    'Questi dati ci servono per metterti a disposizione un account, per farti accedere e per assicurarci che l’indirizzo e-mail sia davvero tuo. La base giuridica è l’art. 6 par. 1 lett. b GDPR.',
                ],
            ],
            [
                'heading' => 'Contenuti dell’applicazione',
                'body' => [
                    'Congelatori, cassetti e articoli (nome, quantità, categoria, data di scadenza) nonché appartenenze e inviti. Questi dati sono la funzione stessa dell’applicazione: li vedono tutte le persone che hanno accesso al rispettivo congelatore – il proprietario e le persone che ha invitato.',
                    'Un link d’invito contiene un token casuale. Ne viene memorizzato solo un hash, non il link stesso; scade dopo sette giorni e può essere usato una volta. La base giuridica è l’art. 6 par. 1 lett. b GDPR.',
                ],
            ],
            [
                'heading' => 'Dati tecnici',
                'body' => [
                    'Quando accedi, l’applicazione crea una sessione. Vengono memorizzati l’identificativo di sessione (come cookie nel tuo browser), il tuo indirizzo IP, l’identificativo del browser (user agent), il momento dell’ultima attività e – se scegli «rimani connesso» – un token di accesso permanente.',
                    'Questi dati servono al funzionamento, alla sicurezza e alla difesa da abusi (art. 6 par. 1 lett. f GDPR). I tentativi di accesso falliti vengono contati per breve tempo, così le password non possono essere provate a ripetizione.',
                ],
            ],
            [
                'heading' => 'Log del provider di hosting',
                'body' => [
                    'Il provider di hosting (:hosting) registra le richieste delle pagine nei file di log del server: indirizzo IP, momento, indirizzo richiesto, codice di stato, quantità di dati trasmessa e identificativo del browser. Ciò è necessario per il funzionamento tecnico, la ricerca degli errori e la difesa dagli attacchi (art. 6 par. 1 lett. f GDPR).',
                    'Per quanto tempo questi log vengano conservati dipende dai termini del provider (di regola pochi giorni); i dettagli sono nella sua informativa sulla privacy.',
                ],
            ],

            [
                'heading' => 'E-mail',
                'body' => [
                    'L’applicazione invia solo e-mail che attivi tu stesso: la conferma del tuo indirizzo e-mail dopo la registrazione e il link per reimpostare la password. Per questo il tuo indirizzo e-mail viene trasmesso al servizio di invio :mail_provider (art. 6 par. 1 lett. b e lett. f GDPR).',
                ],
            ],
            [
                'heading' => 'Basi giuridiche',
                'body' => [
                    'Se sopra non è indicato diversamente, trattiamo i dati sulla base dell’art. 6 par. 1 lett. b GDPR (fornitura dell’applicazione) e lett. f GDPR (sicurezza e stabilità). I dati facoltativi come il nome visualizzato o l’immagine del profilo vengono trattati sulla base del tuo consenso (art. 6 par. 1 lett. a GDPR); puoi modificarli o rimuoverli in qualsiasi momento.',
                ],
                'note' => 'Puoi revocare in qualsiasi momento un consenso dato (art. 7 par. 3 GDPR). La liceità del trattamento effettuato fino a quel momento resta impregiudicata.',
            ],
            [
                'heading' => 'Cookie e memoria locale',
                'body' => [
                    'Questa applicazione non imposta cookie per scopi pubblicitari o di analisi. Un banner per il consenso non è quindi necessario.',
                ],
                'list' => [
                    'laravel_session – cookie tecnicamente necessario per l’accesso. Viene cancellato quando esci e scade dopo 120 minuti di inattività (§ 25 par. 2 n. 2 TDDDG).',
                    'Un cookie di accesso («rimani connesso») – solo se scegli questa opzione nel modulo di accesso. Ti mantiene connesso dopo la chiusura del browser.',
                    'color-scheme nella memoria locale del browser – ricorda se usi l’applicazione in modalità chiara o scura. La voce non contiene identificativi e non viene valutata.',
                ],
                'note' => 'Puoi cancellare queste voci dal browser in qualsiasi momento; poi dovrai solo accedere di nuovo.',
            ],
            [
                'heading' => 'Destinatari',
                'body' => [
                    'I tuoi dati restano in questa applicazione. Non vengono venduti né trasmessi per scopi pubblicitari. Hanno accesso ai dati soltanto:',
                ],
                'list' => [
                    'Hosting: :hosting (sede: :hosting_location). Il provider tratta database, file e log per nostro conto ai sensi dell’art. 28 GDPR.',
                    'Invio delle e-mail: :mail_provider (sede: :mail_location) – esclusivamente per le e-mail di conferma e di reimpostazione della password.',
                    'Persone con accesso a un congelatore: vedono il contenuto di quel congelatore, perché è esattamente la funzione dell’applicazione.',
                ],
                'note' => 'Non avviene alcun trasferimento verso Paesi al di fuori dell’Unione europea se i provider indicati hanno sede nell’UE. In caso contrario si basa sulle clausole contrattuali standard della Commissione europea.',
            ],
            [
                'heading' => 'Durata della conservazione',
                'body' => [
                    'I dati vengono conservati per il tempo necessario allo scopo:',
                ],
                'list' => [
                    'Account (nome visualizzato, indirizzo e-mail, hash della password, lingua): fino alla cancellazione dell’account.',
                    'Immagine del profilo: finché non la rimuovi o non cancelli l’account.',
                    'Congelatori, cassetti e articoli: finché non vengono cancellati o non viene cancellato l’account.',
                    'Sessioni (indirizzo IP, identificativo del browser): fino all’uscita, al più tardi dopo 120 minuti di inattività.',
                    'Link d’invito: sette giorni.',
                    'Link di conferma e-mail e di reimpostazione password: 60 minuti.',
                    'Voci di reimpostazione della password: finché il link non viene usato o non ne viene richiesto uno nuovo.',
                    'Log del server del provider di hosting: secondo i suoi termini.',
                ],
            ],
            [
                'heading' => 'I tuoi diritti',
                'body' => [
                    'Nei confronti del titolare hai i seguenti diritti:',
                ],
                'list' => [
                    'Accesso ai dati trattati (art. 15 GDPR)',
                    'Rettifica dei dati inesatti (art. 16 GDPR)',
                    'Cancellazione (art. 17 GDPR)',
                    'Limitazione del trattamento (art. 18 GDPR)',
                    'Portabilità dei dati (art. 20 GDPR)',
                    'Opposizione ai trattamenti basati su interessi legittimi (art. 21 GDPR)',
                ],
                'note' => 'Per l’accesso, la rettifica o la portabilità basta un messaggio a :operator_email. Puoi inoltre presentare reclamo a un’autorità di controllo (art. 77 GDPR) – per esempio a quella del tuo luogo di residenza.',
            ],
            [
                'heading' => 'Cancellare l’account',
                'body' => [
                    'Nel profilo trovi il pulsante «cancella account». Per sicurezza devi inserire la tua password. Vengono cancellati l’account, l’immagine del profilo, le sessioni di accesso e tutti i congelatori che hai creato, con cassetti e articoli.',
                    'I congelatori che altri hanno condiviso con te restano – lì perdi solo l’accesso. Una cancellazione non è reversibile. In alternativa basta un messaggio a :operator_email.',
                ],
            ],
            [
                'heading' => 'Sicurezza',
                'body' => [
                    'La connessione all’applicazione è cifrata (HTTPS). Le password vengono memorizzate solo come hash e non sono leggibili nemmeno dal fornitore. Le immagini del profilo si trovano in una cartella non accessibile pubblicamente e vengono consegnate solo a persone connesse.',
                ],
            ],
            [
                'heading' => 'Nessuna decisione automatizzata',
                'body' => [
                    'Non avvengono profilazione né decisioni automatizzate ai sensi dell’art. 22 GDPR. Il semaforo delle scadenze dell’applicazione è un semplice calcolo a partire dalla data che hai inserito.',
                ],
            ],
            [
                'heading' => 'Modifiche a questa informativa',
                'body' => [
                    'Se l’applicazione cambia, questa informativa viene aggiornata. Vale di volta in volta la versione pubblicata qui; la data dell’ultima modifica è indicata in fondo alla pagina.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Condizioni d’uso',
        'heading' => 'Condizioni d’uso',
        'intro' => 'Le regole per l’uso di :app.',
        'sections' => [
            [
                'heading' => 'Ambito di applicazione',
                'body' => [
                    'Queste condizioni d’uso valgono per l’uso di :app. Fornitore è la persona indicata nelle note legali. Con la registrazione le accetti.',
                ],
            ],
            [
                'heading' => 'Che cosa offre l’applicazione',
                'body' => [
                    'L’applicazione aiuta a gestire insieme il contenuto dei congelatori e a ricordare le date di scadenza. È uno strumento di aiuto e non una garanzia sulla qualità o sulla commestibilità degli alimenti: nomi, quantità e date di scadenza provengono da chi usa l’applicazione e ogni persona decide da sé se un alimento è ancora da consumare.',
                ],
            ],
            [
                'heading' => 'Account',
                'body' => [
                    'Per usare l’applicazione serve un account. Vale quanto segue:',
                ],
                'list' => [
                    'Indichi un indirizzo e-mail che ti appartiene e lo confermi tramite il link nell’e-mail di conferma.',
                    'Tieni segreta la tua password e informi il fornitore se sospetti che qualcun altro abbia accesso al tuo account.',
                    'Un account per persona; non è consentito cedere l’account ad altri.',
                    'Devi avere almeno 16 anni. Le persone più giovani possono usare l’applicazione solo con il consenso di chi esercita la responsabilità genitoriale.',
                    'I tuoi dati devono essere corretti e aggiornati.',
                ],
            ],
            [
                'heading' => 'Regole d’uso',
                'body' => [
                    'Nell’uso dell’applicazione:',
                ],
                'list' => [
                    'Nessun contenuto illecito, offensivo o denigratorio.',
                    'Nessun tentativo di disturbare o sovraccaricare l’applicazione, di leggerne i dati in modo automatizzato o di aggirare i diritti di accesso.',
                    'I link d’invito sono personali: chi ha il link ottiene accesso al congelatore. Trasmettilo quindi solo a persone che vuoi davvero invitare.',
                    'Nessun abuso di account altrui e nessun caricamento di contenuti di cui non detieni i diritti.',
                ],
            ],
            [
                'heading' => 'Disponibilità',
                'body' => [
                    'L’applicazione è offerta gratuitamente. Non esiste un diritto a una disponibilità costante; manutenzione, modifiche e anche la cessazione del servizio sono possibili. Annota altrove le informazioni importanti se ti servono più a lungo.',
                ],
            ],

            [
                'heading' => 'Responsabilità',
                'body' => [
                    'Il fornitore risponde senza limiti in caso di dolo e colpa grave, di lesione della vita, del corpo o della salute e ai sensi della legge sulla responsabilità da prodotto. In caso di colpa lieve risponde solo per la violazione di obblighi contrattuali essenziali e, nell’importo, limitatamente al danno prevedibile e tipico di questo tipo di contratto. Per il resto la responsabilità è esclusa – in particolare per i danni derivanti da decisioni che prendi sulla base delle indicazioni memorizzate nell’applicazione e per la perdita di dati, se il danno poteva essere evitato con un backup ragionevole.',
                ],
            ],
            [
                'heading' => 'Durata e cessazione',
                'body' => [
                    'Puoi terminare l’uso in qualsiasi momento cancellando l’account nel profilo. Il fornitore può bloccare o cancellare un account in caso di violazione di queste condizioni o se la sicurezza dell’applicazione lo richiede. I dati cancellati non possono essere ripristinati.',
                ],
            ],
            [
                'heading' => 'Modifiche a queste condizioni',
                'body' => [
                    'Se l’applicazione o la situazione giuridica cambiano, queste condizioni possono essere adattate. Le modifiche vengono annunciate nell’applicazione. Se non sei d’accordo, puoi terminare l’uso e cancellare l’account.',
                ],
            ],
            [
                'heading' => 'Disposizioni finali',
                'body' => [
                    'Si applica il diritto tedesco. Restano impregiudicate le norme imperative a tutela dei consumatori. Se una disposizione è inefficace, le restanti restano valide.',
                ],
            ],
        ],
    ],

    /*
     | Testi che compaiono su tutte e tre le pagine: l’intestazione del layout, la
     | data dell’ultima modifica e le formulazioni neutre per i dati che non sono
     | ancora configurati.
     */
    'shared' => [
        'legal_heading' => 'Note legali',
        'back_to_app' => 'Torna all’applicazione',
        'to_login' => 'Vai all’accesso',
        'updated' => 'Ultima modifica: :date',
        'contact' => 'Domande su queste pagine? Scrivi a :operator_email.',
        'placeholders' => [
            'operator' => 'il fornitore di questa applicazione',
            'operator_email' => 'l’indirizzo indicato nelle note legali',
            'hosting' => 'il provider di hosting di questa applicazione',
            'mail_provider' => 'il servizio di invio e-mail di questa applicazione',
            'location' => 'da chiedere al fornitore',
            'date' => 'vedi configurazione',
        ],
        'missing' => [
            'name' => 'Nome del fornitore',
            'street' => 'Via e numero civico',
            'city' => 'Codice postale e località',
            'email' => 'Indirizzo e-mail per i contatti',
        ],
    ],
];
