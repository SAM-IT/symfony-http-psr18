<?php

declare(strict_types=1);

namespace SamIt\SymfonyHttpPsr18;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;

class SymfonyHttpResponseAdapter implements SymfonyResponseInterface
{
    public function __construct(private ResponseInterface $response, private mixed $userData, private RequestInterface $request)
    {
    }


    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    private function throw(): void
    {
        if ($this->response->getStatusCode() >= 500) {
            throw new class($this) extends \RuntimeException implements ServerExceptionInterface {
                public function __construct(private SymfonyResponseInterface $response)
                {
                    parent::__construct();
                }

                public function getResponse(): SymfonyResponseInterface
                {
                    return $this->response;
                }
            };
        }
        if ($this->response->getStatusCode() >= 400) {
            throw new class($this) extends \RuntimeException implements ClientExceptionInterface {
                public function __construct(private SymfonyResponseInterface $response)
                {
                    parent::__construct();
                }

                public function getResponse(): SymfonyResponseInterface
                {
                    return $this->response;
                }
            };
        }
        if ($this->response->getStatusCode() >= 300) {
            throw new class($this) extends \RuntimeException implements RedirectionExceptionInterface {
                public function __construct(private SymfonyResponseInterface $response)
                {
                    parent::__construct();
                }

                public function getResponse(): SymfonyResponseInterface
                {
                    return $this->response;
                }
            };
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->throw();
        }
        $result = [];
        foreach ($this->response->getHeaders() as $key => $values) {
            $result[strtolower($key)] = array_values($values);
        }
        return $result;
    }

    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->throw();
        }
        return $this->response->getBody()->getContents();
    }

    /**
     * @param bool $throw
     * @return array<mixed>
     * @throws \JsonException
     */
    public function toArray(bool $throw = true): array
    {
        if ($throw) {
            $this->throw();
        }
        if (str_starts_with($this->response->getHeaderLine('Content-Type'), 'application/json')) {
            $decoded = json_decode($this->response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new class() extends \RuntimeException implements DecodingExceptionInterface {
        };
    }

    public function cancel(): void
    {
        $this->response->getBody()->close();
    }

    public function getInfo(string|null $type = null): mixed
    {
        $data = [
            'canceled' => false,
            'error' => null,
            'http_code' => $this->response->getStatusCode(),
            'http_method' => $this->request->getMethod(),
            // we never follow redirects
            'redirect_count' => 0,
            'redirect_url' => in_array($this->response->getStatusCode(), [301, 302], true) ? $this->response->getHeaderLine('Location') : null,
            'response_headers' => $this->response->getHeaders(),
            'start_time' => null,
            'url' => (string) $this->request->getUri(),
            'user_data' => $this->userData
        ];
        if (!isset($type)) {
            return $data;
        }
        return $data[$type] ?? null;
    }
}
