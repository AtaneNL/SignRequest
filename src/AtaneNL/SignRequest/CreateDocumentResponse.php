<?php
namespace AtaneNL\SignRequest;

use anlutro\cURL\Response;

class CreateDocumentResponse {
    
    public $uuid;
    public $securityHash;
    
    public function __construct(Response $response) {
        $body = json_decode($response->body);
        $this->uuid = $body->uuid;
        $this->securityHash = $body->security_hash;
    }
    
}
