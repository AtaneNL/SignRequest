<?php
namespace AtaneNL\SignRequest;

use anlutro\cURL;

class Client {
    
    const API_URL = "https://signrequest.com/api/v1";
    
    private $token;
    /* @var $curl \anlutro\cURL\cURL */
    private $curl; 
    
    public function __construct($token) {
        $this->token = $token;
        $this->curl = new cURL\cURL();
    }
    
    public function createSignRequest($file, $identifier = null) {
        $file = curl_file_create($file);
        $response = $this->newRequest('documents')
                ->setHeader("Content-Type", "multipart/form-data")
                ->setData(['file'=>$file, 'external_id'=>$identifier])
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