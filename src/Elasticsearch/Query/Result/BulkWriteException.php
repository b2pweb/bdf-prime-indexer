<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Indexer\Exception\QueryExecutionException;

/**
 * Exception thrown when a bulk write operation fails
 *
 * @see BulkResultSet::checkErrors() For throwing this exception
 */
class BulkWriteException extends QueryExecutionException
{
    /**
     * @var list<array{
     *     _index: string,
     *     _id: string,
     *     status: int,
     *     error: array{
     *         type: string,
     *         reason: string,
     *         caused_by?: array,
     *     },
     * }>
     */
    private array $data;

    /**
     * @param list<array{
     *     _index: string,
     *     _id: string,
     *     status: int,
     *     error: array{
     *         type: string,
     *         reason: string,
     *         caused_by?: array,
     *     },
     * }> $data
     */
    public function __construct(array $data)
    {
        parent::__construct(self::buildMessage($data));

        $this->data = $data;
    }

    /**
     * Get errors details
     *
     * @return list<array{
     *     _index: string,
     *     _id: string,
     *     status: int,
     *     error: array{
     *         type: string,
     *         reason: string,
     *         caused_by?: array,
     *     },
     * }>
     */
    public function errors(): array
    {
        return $this->data;
    }

    /**
     * @param list<array{
     *     _index: string,
     *     _id: string,
     *     status: int,
     *     error: array{
     *         type: string,
     *         reason: string,
     *         caused_by?: array,
     *     },
     * }> $data
     */
    private static function buildMessage(array $data): string
    {
        $message = 'Error during execution of bulk write query : ' . PHP_EOL;

        foreach ($data as $item) {
            $message .= '- ' . $item['error']['reason'];

            if (isset($item['error']['caused_by'])) {
                $message .= ' Caused by: '.$item['error']['caused_by']['reason'];
            }

            $message .= PHP_EOL;
        }

        return $message;
    }
}
