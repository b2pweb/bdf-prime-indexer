<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class MatchPhraseTest
 */
class MatchPhraseTest extends TestCase
{
    /**
     *
     */
    public function test_compile()
    {
        $this->assertEquals(['match_phrase' => ['my_field' => 'my_value']], (new MatchPhrase('my_field', 'my_value'))->compile(new ElasticsearchGrammar()));
    }
}
