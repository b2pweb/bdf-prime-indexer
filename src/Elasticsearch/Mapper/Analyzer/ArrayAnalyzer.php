<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer;

/**
 * Configurable analyzer using an array declaration
 */
final class ArrayAnalyzer implements AnalyzerInterface
{
    /**
     * @var array
     */
    private array $declaration;


    /**
     * ArrayAnalyzer constructor.
     *
     * @param array $declaration
     */
    public function __construct(array $declaration)
    {
        $this->declaration = $declaration;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(): array
    {
        return array_diff_key($this->declaration, ['filter' => null, 'tokenizer' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toIndex($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function tokenizer()
    {
        return $this->declaration['tokenizer'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function filters(): array
    {
        return $this->declaration['filter'] ?? [];
    }
}
