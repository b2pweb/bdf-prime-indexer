<?php

namespace ElasticsearchTestFiles;

class CityMapper extends \Bdf\Prime\Mapper\Mapper
{
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'city',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->string('zipCode')
            ->integer('population')
            ->string('country')
            ->boolean('enabled');
    }
}