<?php
namespace HoneySens\app\adapters;

use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use WpOrg\Requests\Requests;

/**
 * Interacts with a Docker registry.
 * Supports querying for repositories and tags, as well
 * as removal of tags. Uploading new content to the registry
 * is out of scope for this adapter and instead performed
 * by a task queue worker.
 */
class RegistryAdapter {

    /**
     * Returns the availability of the registry by querying it.
     */
    public function isAvailable(): bool {
        try {
            $response = Requests::get(sprintf('%s/', $this->getRegistryURL()));
        } catch(\Exception $e)  {
            return false;
        }
        return $response->status_code == 200;
    }

    /**
     * Queries the registry and returns an array with available repositories.
     */
    public function getRepositories(): array {
        $response = Requests::get(sprintf('%s/_catalog', $this->getRegistryURL()));
        return json_decode($response->body);
    }

    /**
     * Queries the registry and returns an array with available tags
     * for a given repository.
     *
     * @param string $repository Name of the repository to query for.
     * @throws NotFoundException
     */
    public function getTags(string $repository): array {
        $response = Requests::get(sprintf('%s/%s/tags/list', $this->getRegistryURL(), $repository));
        if(!$response->success) throw new NotFoundException();
        return json_decode($response->body)->tags;
    }

    public function removeRepository(string $repository) {
        // TODO
    }

    /**
     * Removes a single tag from the registry.
     *
     * @param string $repository Name of the repository that contains the tag to delete.
     * @param string $tag Name of the tag to delete.
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function removeTag(string $repository, string $tag): void {
        if(!$this->isAvailable()) throw new \Exception('Registry offline');
        $response = Requests::get(sprintf('%s/%s/manifests/%s', $this->getRegistryURL(), $repository, $tag),
            array('Accept' => 'application/vnd.docker.distribution.manifest.v2+json'),
            array());
        if(!isset($response->headers['Docker-Content-Digest']))
            throw new NotFoundException();
        $digest = $response->headers['Docker-Content-Digest'];
        $response = Requests::delete(sprintf('%s/%s/manifests/%s', $this->getRegistryURL(), $repository, $digest));
        if(!$response->success) throw new BadRequestException();
    }

    /**
     * Builds a Docker v2 registry URL string.
     * The registry endpoint is taken from environment variables.
     */
    private function getRegistryURL(): string {
        return sprintf('http://%s:%u/v2', getenv('HS_REGISTRY_HOST'), getenv('HS_REGISTRY_PORT'));
    }
}
