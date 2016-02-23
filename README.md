# signrequest
Client lib for the SignRequest API

## Usage
```php

// initialize the client
$client = new \AtaneNL\SignRequest\Client('yourApiKey123');

// send a document to SignRequest
$cdr = $client->createDocument('/path/to/file', 'localReferenceToThisFile');

// notify intended signers
$result = $client->sendSignRequest($cdr->uuid, 'sender@company.com', ['recipient1@signers-united.com','recipient2@signers-united.com'], "Please sign this");
```

The default language is set to dutch, change it by:
```php
\AtaneNL\SignRequest\Client::$defaultLanguage = 'en';
```