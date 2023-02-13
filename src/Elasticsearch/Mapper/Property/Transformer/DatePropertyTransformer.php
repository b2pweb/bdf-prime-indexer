<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertyInterface;
use DateTime;
use DateTimeInterface;
use IntlDateFormatter;

use function is_scalar;

/**
 * Convert PHP DateTime to elasticsearch date format
 *
 * The format is resolved in this order:
 * - Format passed in constructor, using the format of {@see DateTimeInterface::format()}
 * - Format declared on elasticsearch property, following the ICU format (e.g. "yyyy-MM-dd HH:mm:ss")
 * - Default format: {@see DateTimeInterface::ATOM}
 *
 * If the value is not a DateTimeInterface, the value is not transformed
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
 */
final class DatePropertyTransformer implements PropertyTransformerInterface
{
    private ?string $format;

    /**
     * @param string|null $format The date format, following {@see DateTimeInterface::format()} one
     */
    public function __construct(?string $format = null)
    {
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function toIndex(PropertyInterface $property, $value)
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }

        if ($this->format) {
            return $value->format($this->format);
        }

        $icuFormat = $property->declaration()['format'] ?? null;

        if (!$icuFormat) {
            return $value->format(DateTimeInterface::ATOM);
        }

        $formatter = new IntlDateFormatter(null);
        $formatter->setPattern($icuFormat);

        return $formatter->format($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex(PropertyInterface $property, $value)
    {
        if (!is_scalar($value) || $value === '') {
            return $value;
        }

        if ($this->format) {
            return DateTime::createFromFormat('!' . $this->format, $value);
        }

        $icuFormat = $property->declaration()['format'] ?? null;

        if (!$icuFormat) {
            return new DateTime($value);
        }

        $formatter = new IntlDateFormatter(null);
        $formatter->setPattern($icuFormat);

        return new DateTime('@' . $formatter->parse($value));
    }
}
