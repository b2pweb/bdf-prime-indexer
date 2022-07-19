<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Response;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;

/**
 * Store aliases for a given index
 */
final class Aliases
{
    private ClientInterface $client;
    private string $index;
    private array $aliases;

    /**
     * @param ClientInterface $client
     * @param string $index
     * @param array<string, mixed> $aliases
     */
    public function __construct(ClientInterface $client, string $index, array $aliases)
    {
        $this->client = $client;
        $this->index = $index;
        $this->aliases = $aliases;
    }

    /**
     * Check if the given alias is present
     *
     * @param string $alias Alias name
     *
     * @return bool
     */
    public function contains(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Get aliases
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->aliases);
    }

    /**
     * Delete all aliases
     *
     * @return void
     */
    public function delete(): void
    {
        $this->client->deleteAliases($this->index, array_keys($this->aliases));
    }

    /**
     * Get the index name
     */
    public function index(): string
    {
        return $this->index;
    }
}
