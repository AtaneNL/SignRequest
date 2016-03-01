<?php
namespace AtaneNL\SignRequest;

use anlutro\cURL\cURL;

class Client {

    const API_BASEURL = "https://signrequest.com/api/v1";

    public static $defaultLanguage = 'nl';

    /* @var $curl \anlutro\cURL\cURL */
    private $curl;
    private $token;
    private $subdomain; // the subdomain

    public function __construct($token, $subdomain= null) {
        $this->token = $token;
        $this->subdomain = $subdomain;
        $this->curl = new cURL();
    }

    /**
     * Send a document to SignRequest.
     * @param string $file The absolute path to a file.
     * @param string $identifier
     * @param string $callbackUrl
     * @return CreateDocumentResponse
     */
    public function createDocument($file, $identifier, $callbackUrl = null) {
        $file = curl_file_create($file);
        $response = $this->newRequest("documents")
                ->setHeader("Content-Type", "multipart/form-data")
                ->setData(['file'=>$file, 'external_id'=>$identifier, 'events_callback_url'=>$callbackUrl])
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
        foreach ( $recipients as &$r ) {
            if (!array_key_exists('language', $r)) {
                $r['language'] = self::$defaultLanguage;
            }
        }
        $response = $this->newRequest("signrequests")
                ->setHeader("Content-Type", "application/json")
                ->setData(json_encode([
                    "document"=>self::API_BASEURL . "/documents/" . $documentId . "/",
                    "from_email"=>$sender,
                    "message"=>$message,
                    "signers"=>$recipients
                    ]))
                ->send();
        $responseObj = json_decode($response);
        if (!$responseObj->uuid) {
            throw new Exceptions\SendSignRequestException($response);
        }
        return $responseObj->uuid;
    }

    /**
     * Setup a base request object.
     * @param type $action
     * @return \anlutro\cURL\Request
     */
    private function newRequest($action) {
        $baseRequest = $this->curl->newRawRequest('post', self::API_BASEURL . "/" . $action . "/")
            ->setHeader("Authorization", "Token " . $this->token)
            ->setData('subdomain', $this->subdomain);
        return $baseRequest;
    }

}