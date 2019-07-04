<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer;

/**
 * Default analyzer, without custom parameters
 */
final class StandardAnalyzer implements AnalyzerInterface
{
    /**
     * {@inheritdoc}
     */
    public function declaration()
    {
        return [
            'type' => 'standard'
        ];
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function filters()
    {
        return [];
    }
}
