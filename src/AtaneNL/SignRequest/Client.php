<?php
namespace AtaneNL\SignRequest;

use anlutro\cURL\cURL;

class Client {
    
    const API_URL = "https://signrequest.com/api/v1";
    
    private $token;
    /* @var $curl \anlutro\cURL\cURL */
    private $curl; 
    
    public function __construct($token) {
        $this->token = $token;
        $this->curl = new cURL();
    }
    
    /**
     * Send a document to SignRequest.
     * @param type $file
     * @param type $identifier
     * @return CreateDocumentResponse
     */
    public function createDocument($file, $identifier = null) {
        $file = curl_file_create($file);
        $response = $this->newRequest("documents")
                ->setHeader("Content-Type", "multipart/form-data")
                ->setData(['file'=>$file, 'external_id'=>$identifier])
                ->send();
        return new CreateDocumentResponse($response);
    }
    
    /**
     * Send a sign request for a created document.
     * @param type $documentId
     * @param type $sender
     * @param type $recipients
     * @param type $message
     */
    public function sendSignRequest($documentId, $sender, $recipients, $message = null) {
        $rcpts = [];
        foreach ( $recipients as $r ) $rcpts []= ["email"=>$r];
        $response = $this->newRequest("signrequests")
                ->setHeader("Content-Type", "application/json")
                ->setData([
                    "document"=>self::API_URL . "/documents/" . $documentId . "/",
                    "from_email"=>$sender,
                    "message"=>$message,
                    "signers"=>json_encode($rcpts)
                    ])
                ->send();
        return $response;
    }
    
    /**
     * 
     * @param type $action
     * @return \anlutro\cURL\Request
     */
    private function newRequest($action) {
        $baseRequest = $this->curl->newRawRequest('post', self::API_URL . "/" . $action . "/")
            ->setHeader("Authorization", "Token " . $this->token);
        return $baseRequest;
    }
    
}