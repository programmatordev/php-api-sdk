<?php

namespace ProgrammatorDev\Api\Builder;

use Http\Message\Authentication;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Chain;
use Http\Message\Authentication\Header;
use Http\Message\Authentication\QueryParam;
use Http\Message\Authentication\RequestConditional;
use Http\Message\Authentication\Wsse;
use Http\Message\RequestMatcher;
use ProgrammatorDev\Api\Authentication\CallbackAuthentication;
use Psr\Http\Message\RequestInterface;

class AuthBuilder
{
    private ?Authentication $authentication = null;

    public function bearer(string $token): self
    {
        return $this->use(new Bearer($token));
    }

    public function basic(string $username, string $password): self
    {
        return $this->use(new BasicAuth($username, $password));
    }

    public function header(string $name, string|array $value): self
    {
        return $this->use(new Header($name, $value));
    }

    public function query(string $name, mixed $value): self
    {
        return $this->use(new QueryParam([$name => $value]));
    }

    public function wsse(string $username, string $password, string $hashAlgorithm = 'sha1'): self
    {
        return $this->use(new Wsse($username, $password, $hashAlgorithm));
    }

    public function conditional(RequestMatcher $matcher, Authentication $authentication): self
    {
        return $this->use(new RequestConditional($matcher, $authentication));
    }

    public function chain(Authentication ...$authentications): self
    {
        return $this->use(new Chain($authentications));
    }

    /**
     * @param callable(RequestInterface): RequestInterface $callback
     */
    public function custom(callable $callback): self
    {
        return $this->use(new CallbackAuthentication($callback));
    }

    public function use(Authentication $authentication): self
    {
        // Authentication is intentionally replaced, not appended. Use chain()
        // when multiple authentication strategies must run on the same request.
        $this->authentication = $authentication;

        return $this;
    }

    public function getAuthentication(): ?Authentication
    {
        return $this->authentication;
    }
}
