<?php
declare(strict_types=1);

namespace SamIt\SymfonyHttpPsr18;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class SymfonyHttpClient implements SymfonyHttpClientInterface
{
    private array $defaults = self::OPTIONS_DEFAULTS;

    public function __construct(
        private ClientInterface $psrClient,
        private StreamFactoryInterface $streamFactory,
        private RequestFactoryInterface $requestFactory
    )
    {
    }

    /**
     * We violate the textual requirement that a response must be lazy.
     * Since the initial use case for this component is to use the Symfony Mailer without the Symfony HTTP Client and the
     * implementations all immediately call `getStatusCode` on the response, there is no effective laziness anyway.
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // Create the request
        $request = $this->requestFactory->createRequest($method, $url);
        // Handle options.
        $options = array_merge($this->defaults, $options);

        unset($options['extra']); // Extra options may be ignored if not supported

        if ($options['json']) {
            $body = $this->streamFactory->createStream(json_encode($options['json'], JSON_THROW_ON_ERROR));
            $request = $request->withHeader('Content-Type', 'application/json')
                ->withBody($body);
            unset($options['json']);
        }

        if ($options['auth_bearer']) {
            $request = $request->withHeader("Authorization", "Bearer {$options['auth_bearer']}");
            unset($options['auth_bearer']);
        }
        if (!empty($options)) {
            throw new TransportException('Unsupported options where passed');
        }


        $response = $this->psrClient->sendRequest($request);
        return new SymfonyHttpResponseAdapter($response);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new TransportException('Not supported');
    }
}
