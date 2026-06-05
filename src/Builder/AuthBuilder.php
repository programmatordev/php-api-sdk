<?php

namespace ProgrammatorDev\Api\Builder;

use Http\Message\Authentication;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Chain;
use Http\Message\Authentication\Header;
use Http\Message\Authentication\QueryParam;
use ProgrammatorDev\Api\Authentication\CallbackAuthentication;
use Psr\Http\Message\RequestInterface;

class AuthBuilder
{
    /** @var Authentication[] */
    private array $authentications = [];

    public function bearer(string $token): self
    {
        return $this->chain(new Bearer($token));
    }

    public function basic(string $username, string $password): self
    {
        return $this->chain(new BasicAuth($username, $password));
    }

    public function header(string $name, string|array $value): self
    {
        return $this->chain(new Header($name, $value));
    }

    public function query(string $name, mixed $value): self
    {
        return $this->chain(new QueryParam([$name => $value]));
    }

    public function chain(Authentication ...$authentications): self
    {
        foreach ($authentications as $authentication) {
            $this->authentications[] = $authentication;
        }

        return $this;
    }

    /**
     * @param callable(RequestInterface): RequestInterface $callback
     */
    public function custom(callable $callback): self
    {
        $this->authentications[] = new CallbackAuthentication($callback);

        return $this;
    }

    public function authentication(): ?Authentication
    {
        if ($this->authentications === []) {
            return null;
        }

        if (count($this->authentications) === 1) {
            return $this->authentications[0];
        }

        return new Chain($this->authentications);
    }
}
