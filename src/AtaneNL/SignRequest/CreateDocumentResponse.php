<?php
namespace AtaneNL\SignRequest;

use anlutro\cURL\Response;

class CreateDocumentResponse {
    
    public $uuid;
    public $url;
    public $securityHash;
    
    public function __construct(Response $response) {
        $body = json_decode($response->body);
        $this->uuid = $body->uuid;
        $this->url = $body->url;
        $this->securityHash = $body->security_hash;
    }
    
}
