<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Expression;

use JsonSerializable;

/**
 * With scripting, you can evaluate custom expressions in Elasticsearch.
 * For example, you can use a script to return a computed value as a field or evaluate a custom score for a query.
 *
 * The default scripting language is Painless.
 * Additional lang plugins are available to run scripts written in other languages. You can specify the language of the script anywhere that scripts run.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/modules-scripting.html
 */
final class Script implements JsonSerializable
{
    public const LANG_PAINLESS = 'painless';
    public const LANG_EXPRESSION = 'expression';
    public const LANG_MUSTACHE = 'mustache';

    private string $source;
    private string $lang;
    private ?array $params;

    /**
     * @param string $source Script expression to execute
     * @param self::LANG_*|string $lang Used language
     * @param array<string, mixed>|null $params Optional parameters for the script.
     */
    public function __construct(string $source, string $lang = self::LANG_PAINLESS, ?array $params = null)
    {
        $this->source = $source;
        $this->lang = $lang;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $compiled = [
            'source' => $this->source,
            'lang' => $this->lang
        ];

        if ($this->params) {
            $compiled['params'] = $this->params;
        }

        return $compiled;
    }
}
