<?php

namespace Shieldo\GuzzlePromisePlugin;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Solarium\Core\Client\Adapter\Guzzle as GuzzleAdapter;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response;
use Solarium\Core\Plugin\AbstractPlugin;
use Solarium\Core\Query\AbstractQuery;

class GuzzlePromisePlugin extends AbstractPlugin
{
    /**
     * @param AbstractQuery        $query
     * @param string|Endpoint|null $endpoint
     * @return PromiseInterface
     */
    public function queryAsync($query, $endpoint = null)
    {
        /** @var \GuzzleHttp\ClientInterface $guzzle */
        $guzzle = $this->client->getAdapter()->getGuzzleClient();
        $request = $this->client->createRequest($query);
        $method = $request->getMethod();
        $endpoint = $this->client->getEndpoint($endpoint);

        $psrRequest = new PsrRequest(
            $method,
            $endpoint->getBaseUri().$request->getUri(),
            $this->getRequestHeaders($request),
            $this->getRequestBody($request)
        );
        $authData = $endpoint->getAuthentication();
        $requestOptions = [
            RequestOptions::HEADERS => $this->getRequestHeaders($request),
            RequestOptions::TIMEOUT => $endpoint->getTimeout(),
            RequestOptions::CONNECT_TIMEOUT => $endpoint->getTimeout(),
        ];
        if (!empty($authData['username']) && !empty($authData['password'])) {
            $requestOptions[RequestOptions::AUTH] = [$authData['username'], $authData['password']];
        }

        return $guzzle->sendAsync($psrRequest, $requestOptions)
            ->then(
                function (ResponseInterface $response) {
                    $responseHeaders = [
                        "HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} "
                        . $response->getReasonPhrase(),
                    ];

                    foreach ($response->getHeaders() as $key => $value) {
                        $responseHeaders[] = "{$key}: " . implode(', ', $value);
                    }

                    return new Response((string) $response->getBody(), $responseHeaders);
                }
            );
    }

    /**
     * @param AbstractQuery[] $queries
     * @param int|null        $concurrency
     */
    public function queryParallel($queries, $concurrency = null)
    {
        $promiseGenerator = function () use ($queries) {
            foreach ($queries as $idx => $query) {
                if (!is_array($query)) {
                    $query = [
                        'query' => $query,
                        'endpoint' => null,
                    ];
                }
                yield $idx => $this->queryAsync(
                    $query['query'],
                    (isset($query['endpoint']) ? $query['endpoint'] : null)
                );
            }
        };
        $results = [];
        \GuzzleHttp\Promise\each_limit(
            $promiseGenerator(),
            $concurrency,
            function ($value, $idx) use (&$results) {
                $results[$idx] = $value;
            }
        );

        return $results;
    }

    protected function initPluginType()
    {
        $this->client->setAdapter(GuzzleAdapter::class);
    }

    private function getRequestBody(Request $request)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if ($request->getFileUpload()) {
            return fopen($request->getFileUpload(), 'r');
        }

        return $request->getRawData();
    }

    private function getRequestHeaders(Request $request)
    {
        $headers = [];
        foreach ($request->getHeaders() as $headerLine) {
            list($header, $value) = explode(':', $headerLine);
            if ($header = trim($header)) {
                $headers[$header] = trim($value);
            }
        }

        if (!isset($headers['Content-Type'])) {
            if ($request->getMethod() == Request::METHOD_GET) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
            } else {
                $headers['Content-Type'] = 'application/xml; charset=utf-8';
            }
        }

        return $headers;
    }
}
