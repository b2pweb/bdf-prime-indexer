<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Response;

use ArrayAccess;

/**
 * Results of a search query
 *
 * @implements ArrayAccess<string, mixed>
 */
final class SearchResults implements ArrayAccess
{
    private ?string $scrollId;
    private int $took;
    private bool $timedOut;
    private array $shards;
    private int $total;
    private bool $isAccurateCount;
    private ?float $maxScore;
    private array $hits;
    private array $raw;

    /**
     * @param string|null $scrollId
     * @param int $took
     * @param bool $timedOut
     * @param array $shards
     * @param int $total
     * @param bool $isAccurateCount
     * @param float|null $maxScore
     * @param array $hits
     * @param array $raw
     */
    public function __construct(?string $scrollId, int $took, bool $timedOut, array $shards, int $total, bool $isAccurateCount, ?float $maxScore, array $hits, array $raw)
    {
        $this->scrollId = $scrollId;
        $this->took = $took;
        $this->timedOut = $timedOut;
        $this->shards = $shards;
        $this->total = $total;
        $this->isAccurateCount = $isAccurateCount;
        $this->maxScore = $maxScore;
        $this->hits = $hits;
        $this->raw = $raw;
    }

    /**
     * Identifier for the search and its search context.
     * You can use this scroll ID with the scroll API to retrieve the next batch of search results for the request. See Scroll search results.
     * This parameter is only returned if the scroll query parameter is specified in the request.
     */
    public function scrollId(): ?string
    {
        return $this->scrollId;
    }

    /**
     * Milliseconds it took Elasticsearch to execute the request.
     *
     * This value is calculated by measuring the time elapsed between receipt of a request on the coordinating node and the time at which the coordinating node is ready to send the response.
     *
     * Took time includes:
     * - Communication time between the coordinating node and data nodes
     * - Time the request spends in the search thread pool, queued for execution
     * - Actual execution time
     *
     * Took time does not include:
     * - Time needed to send the request to Elasticsearch
     * - Time needed to serialize the JSON response
     * - Time needed to send the response to a client
     */
    public function took(): int
    {
        return $this->took;
    }

    /**
     * If true, the request timed out before completion; returned results may be partial or empty
     */
    public function timedOut(): bool
    {
        return $this->timedOut;
    }

    /**
     * Contains a count of shards used for the request
     *
     * @return array{
     *     total: int,
     *     successful: int,
     *     skipped: int,
     *     failed: int
     * }
     */
    public function shards(): array
    {
        return $this->shards;
    }

    /**
     * Total number of matching documents
     *
     * @see SearchResults::isAccurateCount()
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Indicates whether the number of matching documents of `total()` is accurate or a lower bound
     *
     * @see SearchResults::total()
     */
    public function isAccurateCount(): bool
    {
        return $this->isAccurateCount;
    }

    /**
     * Highest returned document _score.
     * This value is null for requests that do not sort by _score.
     */
    public function maxScore(): ?float
    {
        return $this->maxScore;
    }

    /**
     * Array of returned document objects
     *
     * @return array{
     *     _index: string,
     *     _id: string,
     *     _score: float,
     *     _source: array,
     *     fields: array
     * }
     */
    public function hits(): array
    {
        return $this->hits;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->raw[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->raw[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Read-only object');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Read-only object');
    }
}
