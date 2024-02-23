<?php

namespace ProgrammatorDev\Api;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Exception;
use Http\Message\Authentication;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseEvent;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Helper\StringHelperTrait;
use ProgrammatorDev\YetAnotherPhpValidator\Exception\ValidationException;
use ProgrammatorDev\YetAnotherPhpValidator\Validator;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Api
{
    use StringHelperTrait;

    private ?string $baseUrl = null;

    private ClientBuilder $clientBuilder;

    private ?Authentication $authentication = null;

    private EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->clientBuilder ??= new ClientBuilder();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @throws MissingConfigException
     * @throws Exception
     */
    protected function request(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|StreamInterface $body = null
    ): mixed
    {
        if (!$this->getBaseUrl()) {
            throw new MissingConfigException('A base URL must be set.');
        }

        // https://docs.php-http.org/en/latest/message/authentication.html
        if ($authentication = $this->getAuthentication()) {
            $this->clientBuilder->addPlugin(new AuthenticationPlugin($authentication));
        }

        $response = $this->clientBuilder->getClient()->send(
            $method,
            $this->createUri($path, $query),
            $headers,
            $body
        );

        $this->eventDispatcher->dispatch(new PostRequestEvent($response));

        $contents = $response->getBody()->getContents();

        return $this->eventDispatcher
            ->dispatch(new ResponseEvent($contents))
            ->getContents();
    }

    protected function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @throws ValidationException
     */
    protected function setBaseUrl(string $baseUrl): self
    {
        Validator::url()->assert($baseUrl);

        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getClientBuilder(): ?ClientBuilder
    {
        return $this->clientBuilder;
    }

    public function setClientBuilder(ClientBuilder $clientBuilder): self
    {
        $this->clientBuilder = $clientBuilder;

        return $this;
    }

    protected function getAuthentication(): ?Authentication
    {
        return $this->authentication;
    }

    protected function setAuthentication(?Authentication $authentication): self
    {
        $this->authentication = $authentication;

        return $this;
    }

    protected function addPostRequestHandler(callable $handler, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(PostRequestEvent::class, $handler, $priority);

        return $this;
    }

    protected function addResponseHandler(callable $handler, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(ResponseEvent::class, $handler, $priority);

        return $this;
    }

    private function createUri(string $path, array $query = []): string
    {
        $uri = $this->reduceDuplicateSlashes($this->getBaseUrl() . $path);

        if (!empty($query)) {
            $uri = \sprintf('%s?%s', $uri, \http_build_query($query));
        }

        return $uri;
    }
}