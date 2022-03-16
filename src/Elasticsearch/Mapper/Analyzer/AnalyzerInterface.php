<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer;

/**
 * Property analyzer for indexing
 * The analyzer may perform data normalisation for optimise indexing
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/analysis-analyzers.html
 */
interface AnalyzerInterface
{
    /**
     * Get the analyzer declaration
     * The tokenizer and filter parameters are optional
     * and will be added corresponding to tokenizer() and filters() methods
     *
     * <code>
     * public function declaration()
     * {
     *     return [
     *         'type' => 'standard',
     *         'stopwords => ['stop1', 'stop2'],
     *         'max_token_length' => 12
     *     ];
     * }
     * </code>
     *
     * @return array
     */
    public function declaration(): array;

    /**
     * Transform the value from index result to PHP usable value
     * Ex: transform CSV "1,5,9" to array [1, 5, 9]
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @see AnalyzerInterface::toIndex() For reverse operation
     */
    public function fromIndex($value);

    /**
     * Normalize the value from PHP value to indexable value
     * Ex: transform array [1, 5, 9] to CSV "1,5,9"
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @see AnalyzerInterface::fromIndex() For reverse operation
     */
    public function toIndex($value);

    /**
     * Get the used tokenizer declaration
     * If returns null, the default tokenizer will be used
     *
     * <code>
     * public function tokenizer()
     * {
     *     return [
     *         'type' => 'pattern',
     *         'pattern' => ','
     *     ];
     * }
     * </code>
     *
     * @return array|string|null
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/analysis-tokenizers.html
     */
    public function tokenizer();

    /**
     * Declare list of filters
     *
     * The filters can be :
     * - A simple string, which represent an already declared (or default) filter
     * - An array with filter name as key, an value as declaration. If no name is given, an name will be generated
     *
     *
     * <code>
     * public function filters()
     * {
     *     return [
     *         'lowercase',
     *         'my_custom_filter' => [
     *             'type' => 'length',
     *             'min'  => 5,
     *             'max'  => 15,
     *         ]
     *     ];
     * }
     * </code>
     *
     * @return array
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/analysis-tokenfilters.html
     */
    public function filters(): array;
}
