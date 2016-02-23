<?php
namespace AtaneNL\SignRequest;
namespace anlutro\cURL;

class Client {
    
    const API_URL = "https://signrequest.com/api/v1";
    
    private $token;
    /* @var $curl \anlutro\cURL\cURL */
    private $curl; 
    
    public function __construct($token) {
        $this->token = $token;
        $this->curl = new cURL();
    }
    
    public function createSignRequest($identifier, $file, $recipients) {
        $file = curl_file_create($file);
        $response = $this->newRequest('documents')->setData(['file'=>$file])->send();
    }
    
    /**
     * 
     * @param type $action
     * @return \anlutro\cURL\Request
     */
    private function newRequest($action) {
        $baseRequest = $this->curl->newRawRequest('post', self::API_URL . "/" . $action . "/")
            ->setHeader("Authorization", $this->token);
        return $baseRequest;
    }
    
}