<?php

namespace Elasticsearch\Grammar;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class ElasticsearchGrammarTest
 */
class ElasticsearchGrammarTest extends TestCase
{
    /**
     * @var ElasticsearchGrammar
     */
    private $grammar;

    protected function setUp(): void
    {
        $this->grammar = new ElasticsearchGrammar();
    }

    /**
     *
     */
    public function test_escape()
    {
        $this->assertEquals('\\\\\(Hello\]', $this->grammar->escape('\(Hello]'));
    }

    /**
     *
     */
    public function test_not()
    {
        $this->assertEquals(
            ['bool' => ['must_not' => [['term' => ['name' => 'John']]]]],
            $this->grammar->not(['term' => ['name' => 'John']])
        );
    }

    /**
     *
     */
    public function test_or()
    {
        $this->assertEquals(
            ['bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    ['term' => ['name' => 'John']],
                    ['term' => ['name' => 'Bob']],
                ]]
            ],
            $this->grammar->or([
                ['term' => ['name' => 'John']],
                ['term' => ['name' => 'Bob']],
            ])
        );
    }

    /**
     *
     */
    public function test_likeToWildcard()
    {
        $this->assertEquals('\*\?P?r*', $this->grammar->likeToWildcard('*?P_r%'));
    }

    /**
     * @dataProvider provideOperators
     */
    public function test_operator($field, $operator, $value, $expected)
    {
        $this->assertEquals($expected, $this->grammar->operator($field, $operator, $value));
    }

    public function provideOperators()
    {
        return [
            ['age', '<', '45', ['range' => ['age' => ['lt' => 45]]]],
            ['age', '<=', '45', ['range' => ['age' => ['lte' => 45]]]],
            ['age', '>', '45', ['range' => ['age' => ['gt' => 45]]]],
            ['age', '>=', '45', ['range' => ['age' => ['gte' => 45]]]],
            ['name', '~=', '[Jj]oh?n', ['regexp' => ['name' => ['value' => '[Jj]oh?n']]]],
            ['name', '~=', ['[Jj]oh?n', '[Bb]ob'], ['bool' => ['minimum_should_match' => 1, 'should' => [['regexp' => ['name' => ['value' => '[Jj]oh?n']]], ['regexp' => ['name' => ['value' => '[Bb]ob']]]]]]],
            ['name', ':like', 'Par%', ['prefix' => ['name' => 'Par']]],
            ['name', ':like', 'P_r%', ['wildcard' => ['name' => 'P?r*']]],
            ['name', ':like', ['P_r%', 'M_r%'], ['bool' => ['minimum_should_match' => 1, 'should' => [['wildcard' => ['name' => 'P?r*']], ['wildcard' => ['name' => 'M?r*']]]]]],
            ['name', ':in', [], ['missing' => ['field' => 'name']]],
            ['name', ':in', ['John', 'Bob'], ['terms' => ['name' => ['John', 'Bob']]]],
            ['name', '!in', [], ['exists' => ['field' => 'name']]],
            ['name', '!in', ['John', 'Bob'], ['bool' => ['must_not' => [['terms' => ['name' => ['John', 'Bob']]]]]]],
            ['age', 'between', [45, 51], ['range' => ['age' => ['gte' => 45, 'lte' => 51]]]],
            ['name', '!=', null, ['exists' => ['field' => 'name']]],
            ['name', '!=', 'Bob', ['bool' => ['must_not' => [['term' => ['name' => 'Bob']]]]]],
            ['name', '!=', ['John', 'Bob'], ['bool' => ['must_not' => [['terms' => ['name' => ['John', 'Bob']]]]]]],
            ['name', '=', null, ['missing' => ['field' => 'name']]],
            ['name', '=', 'John', ['term' => ['name' => 'John']]],
            ['name', '=', ['John', 'Bob'], ['terms' => ['name' => ['John', 'Bob']]]],
        ];
    }
}
