<?php

namespace AtaneNL\SignRequest;

use anlutro\cURL\cURL;
use anlutro\cURL\Request;
use anlutro\cURL\Response;

// TODO move requests to individual classes

class Client
{

    const API_BASEURL = "https://[SUBDOMAIN]signrequest.com/api/v1";

    public static $defaultLanguage = 'nl';

    /* @var $curl \anlutro\cURL\cURL */
    private $curl;
    private $token;
    private $subdomain; // the subdomain

    public function __construct($token, $subdomain = null) {
        $this->token = $token;
        $this->subdomain = $subdomain;
        $this->curl = new cURL();
    }
    
    /**
     * Gets templates from sign request frontend.
     *
     * @return \stdClass response
     *
     * @throws Exceptions\RemoteException
     */
    public function getTemplates()
    {
        $response = $this->newRequest('templates', 'get')->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException($response);
        }
        $responseObj = json_decode($response->body);

        return $responseObj;
    }

    /**
     * Send a document to SignRequest.
     * @param string $file The absolute path to a file.
     * @param string $identifier unique identifier for this file
     * @param string $callbackUrl [optional] url to call when signing is completed
     * @param string $filename [optional] the filename as the signer will see it
     * @param array $settings [optional]
     * @return CreateDocumentResponse
     * @throws Exceptions\SendSignRequestException
     */
    public function createDocument($file, $identifier, $callbackUrl = null, $filename = null, $settings = []) {
        $file = curl_file_create($file, null, $filename);
        $response = $this->newRequest("documents")
            ->setHeader("Content-Type", "multipart/form-data")
            ->setData(array_merge($settings, [
                          'file'                => $file,
                          'external_id'         => $identifier,
                          'events_callback_url' => $callbackUrl
                      ]))
            ->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\SendSignRequestException($response);
        }
        return new CreateDocumentResponse($response);
    }

    /**
     * Send a document to SignRequest using the file_from_url option.
     * @param string $url The URL of the page we want to sign.
     * @param string $identifier
     * @param string $callbackUrl
     * @param array $settings [optional]
     * @return CreateDocumentResponse
     * @throws Exceptions\SendSignRequestException
     */
    public function createDocumentFromURL($url, $identifier, $callbackUrl = null, $settings = []) {
        $response = $this->newRequest("documents")
            ->setHeader("Content-Type", "multipart/form-data")
            ->setData(array_merge($settings, [
                          'file_from_url'       => $url,
                          'external_id'         => $identifier,
                          'events_callback_url' => $callbackUrl
                      ]))
            ->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\SendSignRequestException($response);
        }
        return new CreateDocumentResponse($response);
    }
    
    /**
     * Send a document to SignRequest using the template option.
     *
     * @param string $url         the URL of the template we want to sign
     * @param string $identifier
     * @param string $callbackUrl
     * @param array $settings [optional]
     * @return CreateDocumentResponse
     * @throws Exceptions\SendSignRequestException
     */
    public function createDocumentFromTemplate($url, $identifier = null, $callbackUrl = null, $settings = [])
    {
        $response = $this->newRequest('documents')
            ->setHeader('Content-Type', 'multipart/form-data')
            ->setData(array_merge($settings, [
                'template' => $url,
                'external_id' => $identifier,
                'events_callback_url' => $callbackUrl,
            ]))
            ->send();

        if ($this->hasErrors($response)) {
            throw new Exceptions\SendSignRequestException($response);
        }

        return new CreateDocumentResponse($response);
    }

    /**
     * Add attachment to document sent to SignRequest.
     * @param string $file The absolute path to a file.
     * @param CreateDocumentResponse $cdr
     * @return \stdClass response
     * @throws Exceptions\SendSignRequestException
     */
    public function addAttachmentToDocument($file, CreateDocumentResponse $cdr) {
        $file = curl_file_create($file);
        $response = $this->newRequest("document-attachments")
            ->setHeader("Content-Type", "multipart/form-data")
            ->setData([
                          'file'     => $file,
                          'document' => $cdr->url
                      ])
            ->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\SendSignRequestException($response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj;
    }

    /**
     * Send a sign request for a created document.
     * @param string $documentId uuid
     * @param string $sender Senders e-mail address
     * @param array $recipients
     * @param string $message
     * @param bool $sendReminders Send automatic reminders
     * @param array $settings Add additional request parameters or override defaults
     * @return \stdClass The SignRequest
     * @throws Exceptions\SendSignRequestException
     */
    public function sendSignRequest($documentId, $sender, $recipients, $message = null, $sendReminders = false, $settings = []) {
        foreach ($recipients as &$r) {
            if (!array_key_exists('language', $r)) {
                $r['language'] = self::$defaultLanguage;
            }
        }
        $response = $this->newRequest("signrequests")
            ->setHeader("Content-Type", "application/json")
            ->setData(array_merge([
                                                  "disable_text"        => true,
                                                  "disable_attachments" => true,
                                                  "disable_date"        => true,
                                              ], $settings, [
                                                  "document"       => self::API_BASEURL . "/documents/" . $documentId . "/",
                                                  "from_email"     => $sender,
                                                  "message"        => $message,
                                                  "signers"        => $recipients,
                                                  "send_reminders" => $sendReminders
                                              ]))
            ->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\SendSignRequestException($response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj;
    }

    /**
     * Send a reminder to all recipients who have not signed yet.
     * @param string $signRequestId uuid
     * @return \stdClass response
     * @throws Exceptions\RemoteException
     */
    public function sendSignRequestReminder($signRequestId) {
        $response = $this->newRequest("signrequests/{$signRequestId}/resend_signrequest_email", "post")
            ->setHeader("Content-Type", "application/json")
            ->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException($response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj;
    }

    /**
     * Cancel an existing sign request
     * @param string $signRequestId uuid
     * @return mixed
     * @throws Exceptions\RemoteException
     */
    public function cancelSignRequest($signRequestId) {
        $response = $this
            ->newRequest("signrequests/{$signRequestId}/cancel_signrequest")
            ->setHeader("Content-Type", "application/json")
            ->send();

        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException($response);
        }

        return json_decode($response->body);
    }

    /**
     * Gets the current status for a sign request.
     * @param string $signRequestId uuid
     * @return \stdClass response
     * @throws Exceptions\RemoteException
     */
    public function getSignRequestStatus($signRequestId) {
        $response = $this->newRequest("signrequests/{$signRequestId}", "get")->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException($response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj;
    }

    /**
     * Get a file.
     * @param string $documentId uuid
     * @return \stdClass response
     * @throws Exceptions\RemoteException
     */
    public function getDocument($documentId) {
        $response = $this->newRequest("documents/{$documentId}", "get")->send();
        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException($response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj;
    }

    /**
     * Create a new team.
     * The client should be initialized *without* a subdomain for this method to function properly!!!
     * @param string $name
     * @param string $subdomain
     * @return string
     * @throws Exceptions\LocalException
     * @throws Exceptions\RemoteException
     */
    public function createTeam($name, $subdomain) {
        if ($this->subdomain !== null) {
            throw new Exceptions\LocalException("This request cannot be sent to a subdomain. Initialize the client without a subdomain.");
        }
        $response = $this->newRequest("teams")
            ->setHeader("Content-Type", "application/json")
            ->setData([
                          "name"      => $name,
                          "subdomain" => $subdomain
                      ])
            ->send();

        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException("Unable to create team $name: " . $response);
        }
        $responseObj = json_decode($response->body);
        return $responseObj->subdomain;
    }

    /**
     * @param string $subdomain
     * @return \stdClass
     * @throws Exceptions\LocalException
     * @throws Exceptions\RemoteException
     */
    public function getTeam($subdomain) {
        if ($this->subdomain !== null) {
            throw new Exceptions\LocalException("This request cannot be sent to a subdomain. Initialize the client without a subdomain.");
        }
        $response = $this->newRequest("teams/${subdomain}", 'get')->send();

        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException("Unable to get team $subdomain: " . $response);
        }
        return json_decode($response->body);
    }

    /**
     * @param string $subdomain
     * @param array|\stdClass $params (specify any parameters to update, such as name, logo, phone, primary_color)
     * @return \stdClass
     * @throws Exceptions\LocalException
     * @throws Exceptions\RemoteException
     */
    public function updateTeam($subdomain, $params) {
        if ($this->subdomain !== null) {
            throw new Exceptions\LocalException("This request cannot be sent to a subdomain. Initialize the client without a subdomain.");
        }
        $response = $this->newRequest("teams/${subdomain}", 'patch')
            ->setHeader("Content-Type", "application/json")
            ->setData($params)
            ->send();

        if ($this->hasErrors($response)) {
            throw new Exceptions\RemoteException("Unable to update team $subdomain: " . $response);
        }
        return json_decode($response->body);
    }

    /**
     * Setup a base request object.
     * @param string $action
     * @param string $method post,put,get,delete,option
     * @return Request
     */
    private function newRequest($action, $method = 'post') {
        $baseRequest = $this->curl->newRawRequest($method, $this->getApiUrl() . "/" . $action . "/")
            ->setHeader("Authorization", "Token " . $this->token);
        return $baseRequest;
    }

    /**
     * Set the API url based on the subdomain.
     * @return string API url
     */
    private function getApiUrl() {
        return preg_replace('/\[SUBDOMAIN\]/', ltrim($this->subdomain . ".", "."), self::API_BASEURL);
    }

    /**
     * Check for error in status headers.
     * @param Response $response
     * @return bool
     */
    private function hasErrors($response) {
        return !preg_match('/^20\d$/', $response->statusCode);
    }

}
