<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;

/**
 * Removes the specified document from the index
 */
final class DeleteOperation implements BulkOperationInterface
{
    /**
     * Document id
     * If not set, and cannot be extracted from document, an id will be generated by the index
     *
     * @var string
     */
    private string $id;

    /**
     * Extra option
     *
     * @var array
     */
    private array $options = [];

    /**
     * @param string $id Document ID to delete
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Define a custom option
     *
     * Available options :
     * - require_alias (bool) If true, the action must target an index alias. Defaults to false.
     *
     * @param string $name Option name
     * @param mixed $value Option value
     *
     * @return $this
     */
    public function option(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'delete';
    }

    /**
     * {@inheritdoc}
     */
    public function metadata(ElasticsearchMapperInterface $mapper): array
    {
        return ['_id' => $this->id] + $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function value(ElasticsearchMapperInterface $mapper): ?array
    {
        return null;
    }
}
