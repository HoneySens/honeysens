<?php
namespace HoneySens\app\models;

use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;

class RegistryService {

    protected $appConfig = null;

    public function __construct($config) {
        $this->appConfig = $config;
    }

    public function isAvailable() {
        try {
            $response = \Requests::get(sprintf('%s/', $this->getRegistryURL()));
        } catch(\Exception $e)  {
            return false;
        }
        return $response->status_code == 200;
    }

    public function getRepositories() {
        $response = \Requests::get(sprintf('%s/_catalog', $this->getRegistryURL()));
        return json_decode($response->body);
    }

    public function getTags($repository) {
        $response = \Requests::get(sprintf('%s/%s/tags/list', $this->getRegistryURL(), $repository));
        if(!$response->success) throw new NotFoundException();
        return json_decode($response->body)->tags;
    }

    public function removeRepository($repository) {
        // TODO
    }

    public function removeTag($repository, $tag) {
        if(!$this->isAvailable()) throw new \Exception('Registry offline');
        $response = \Requests::get(sprintf('%s/%s/manifests/%s', $this->getRegistryURL(), $repository, $tag),
            array('Accept' => 'application/vnd.docker.distribution.manifest.v2+json'),
            array());
        if(!isset($response->headers['Docker-Content-Digest']))
            throw new NotFoundException();
        $digest = $response->headers['Docker-Content-Digest'];
        $response = \Requests::delete(sprintf('%s/%s/manifests/%s', $this->getRegistryURL(), $repository, $digest));
        if(!$response->success) throw new BadRequestException();
    }

    private function getRegistryURL() {
        $registryConfig = $this->appConfig['registry'];
        return sprintf('http://%s:%u/v2', $registryConfig['host'], $registryConfig['port']);
    }
}