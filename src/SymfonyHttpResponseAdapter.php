<?php
declare(strict_types=1);

namespace SamIt\SymfonyHttpPsr18;

use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;

class SymfonyHttpResponseAdapter implements SymfonyResponseInterface
{
    public function __construct(private ResponseInterface $response)
    {
    }


    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    private function throw(): void
    {
        if ($this->response->getStatusCode() >= 500) {
            throw new class extends \RuntimeException implements ServerExceptionInterface {
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
            throw new class extends \RuntimeException implements ClientExceptionInterface {
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
            throw new class extends \RuntimeException implements RedirectionExceptionInterface {
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
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->throw();
        }
        $result = [];
        foreach($this->response->getHeaders() as $key => $values) {
            $result[strtolower($key)] = $values;
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

    public function toArray(bool $throw = true): array
    {
        if ($throw) {
            $this->throw();
        }
        if (preg_match('~^application/json~', $this->response->getHeaderLine('Content-Type'))) {
            return json_decode($this->response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        }

        throw new class implements DecodingExceptionInterface {};
    }

    public function cancel(): void
    {
        $this->response->getBody()->close();

    }

    public function getInfo(string $type = null): null|array
    {
        return isset($type) ? null : [];
    }
}
