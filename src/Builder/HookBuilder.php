<?php

namespace ProgrammatorDev\Api\Builder;

use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class HookBuilder
{
    /** @var array<int, list<callable(RequestContext): (RequestInterface|null)>> */
    private array $beforeRequestHooks = [];

    /** @var array<int, list<callable(ResponseContext): (ResponseInterface|null)>> */
    private array $afterResponseHooks = [];

    /**
     * @param callable(RequestContext): (RequestInterface|null) $hook
     */
    public function beforeRequest(callable $hook, int $priority = 0): self
    {
        $this->beforeRequestHooks[$priority] ??= [];
        $this->beforeRequestHooks[$priority][] = $hook;

        return $this;
    }

    /**
     * @param callable(ResponseContext): (ResponseInterface|null) $hook
     */
    public function afterResponse(callable $hook, int $priority = 0): self
    {
        $this->afterResponseHooks[$priority] ??= [];
        $this->afterResponseHooks[$priority][] = $hook;

        return $this;
    }

    public function applyBeforeRequestHooks(RequestContext $context): RequestInterface
    {
        $request = $context->request();

        foreach ($this->sort($this->beforeRequestHooks) as $hook) {
            $replacement = $hook(new RequestContext($request, $context->apiContext()));

            if ($replacement instanceof RequestInterface) {
                $request = $replacement;

                continue;
            }

            if ($replacement !== null) {
                throw new UnexpectedValueException('Before request hooks must return a RequestInterface instance or null.');
            }
        }

        return $request;
    }

    public function applyAfterResponseHooks(ResponseContext $context): ResponseInterface
    {
        $response = $context->response();

        foreach ($this->sort($this->afterResponseHooks) as $hook) {
            $replacement = $hook(new ResponseContext($context->request(), $response, $context->apiContext()));

            if ($replacement instanceof ResponseInterface) {
                $response = $replacement;

                continue;
            }

            if ($replacement !== null) {
                throw new UnexpectedValueException('After response hooks must return a ResponseInterface instance or null.');
            }
        }

        return $response;
    }

    /**
     * @param array<int, list<callable>> $hooks
     * @return list<callable>
     */
    private function sort(array $hooks): array
    {
        if ($hooks === []) {
            return [];
        }

        krsort($hooks);

        return array_values(array_merge(...array_values($hooks)));
    }
}
