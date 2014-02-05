# Didaxo Custom

## Installazione

* Clonare la cartella del plugin nella cartella plugins e denominarla didaxo-custom

* Attivare il plugin

## Configurazione Wordpress

Il plugin come ora costruito funziona se sono presenti le seguenti condizioni

#### Custom Fields

Devono essere settati i seguenti custom fields ( nel formato [post_type] id )

* (Resource) video [string]
* (Level) timer_start [string]
* (Level) timer_end [string]

#### Gerarchia di livelli

L'organizzazione dei livelli deve essere del tipo

Livello 1 {
	Livello 1 - Part 1,
	Livello 2 - Part 2,
	...
}


Livello 2 {
	Livello 2 - Part 1,
	Livello 2 - Part 2,
	...
}

#### Impostazione test

Ricrodarsi di impostare la percentuale corretta per fare in modo che l'utente possa passare il test rispondendo correttamente ad una sola domanda