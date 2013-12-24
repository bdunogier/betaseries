<?php

namespace Patbzh\BetaseriesBundle\Model;

class Client
{
    private $httpClient;
    private $apiVersion;
    private $httpTimeout;
    private $apiKey;
    private $oauthKey;
    private $userAgent;

    public getHttpClient() {
        return $this->httpClient;
    }

    public setHttpClient($httpClient) {
        $this->httpClient = $httpClient;
        return $this;
    }

    public getApiVersion() {
        return $this->apiVersion;
    }

    public setApiVersion($apiVersion) {
        $this->apiVersion = $apiVersion;
        return $this;
    }

    public getHttpTimeout() {
        return $this->httpTimeout;
    }

    public setHttpTimeout($httpTimeout) {
        $this->httpTimeout = $httpTimeout;
        return $this;
    }

    public getApiKey() {
        return $this->apiKey;
    }

    public setApiKey($apiKey) {
        $this->apiKey = $apiKey;
        return $this;
    }

    public getOauthKey() {
        return $this->oauthKey;
    }

    public setOauthKey($oauthKey) {
        $this->oauthKey = $oauthKey;
        return $this;
    }

    public getUserAgent() {
        return $this->userAgent;
    }

    public setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }
}

