<?php

declare(strict_types=1);

namespace SamIt\SymfonyHttpPsr18;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class SymfonyHttpClient implements SymfonyHttpClientInterface
{
    /**
     * @var array{json?: mixed, extra?: mixed, auth_bearer?: mixed, user_data?: mixed}
     */
    private array $defaultOptions = [];
    public function __construct(
        private ClientInterface $psrClient,
        private StreamFactoryInterface $streamFactory,
        private RequestFactoryInterface $requestFactory
    ) {
    }

    /**
     * We violate the textual requirement that a response must be lazy.
     * Since the initial use case for this component is to use the Symfony Mailer without the Symfony HTTP Client and the
     * implementations all immediately call `getStatusCode` on the response, there is no effective laziness anyway.
     * @phpstan-param array{json?: mixed, extra?: mixed, auth_bearer?: string, user_data?: mixed} $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // Create the request
        $request = $this->requestFactory->createRequest($method, $url);

        $options = [...$this->defaultOptions, ...$options];
        unset($options['extra']); // Extra options may be ignored if not supported

        if (isset($options['json'])) {
            $body = $this->streamFactory->createStream(json_encode($options['json'], JSON_THROW_ON_ERROR));
            $request = $request->withHeader('Content-Type', 'application/json')
                ->withBody($body);
            unset($options['json']);
        }

        if (isset($options['auth_bearer']) && is_string($options['auth_bearer'])) {
            $request = $request->withHeader("Authorization", "Bearer {$options['auth_bearer']}");
            unset($options['auth_bearer']);
        }

        if (isset($options['user_data'])) {
            $userData = $options['user_data'];
            unset($options['user_data']);
        }

        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
            unset($options['headers']);
        }

        if (!empty($options)) {
            throw new TransportException('Unsupported options where passed');
        }


        $response = $this->psrClient->sendRequest($request);
        return new SymfonyHttpResponseAdapter($response, userData: $userData ?? null, request: $request);
    }

    public function stream($responses, float|null $timeout = null): ResponseStreamInterface
    {
        throw new TransportException('Not supported');
    }

    /**
     * @param array<string, mixed>$options
     */
    public function withOptions(array $options): static
    {
        $result = clone $this;
        $result->defaultOptions = [];
        foreach (['auth_bearer', 'json', 'user_data'] as $supportedOption) {
            if (isset($options[$supportedOption])) {
                $result->defaultOptions[$supportedOption] = $options[$supportedOption];
                unset($options[$supportedOption]);
            }
        }

        if (!empty($options)) {
            throw new TransportException('Unsupported options where passed');
        }
        return $result;
    }
}
