<?php

namespace Nawado\Myorm\DbContext;

use Nawado\Myorm\Attributes\Model;
use Nawado\Myorm\Connection\Connection;
use Nawado\Myorm\QueryBuilder\QueryBuilder;
use Nawado\Myorm\ReflectionParameters\ParametersHandler;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class DbContext
{
    public function __construct(Connection $connection, ParametersHandler $parametersGetter)
    {
        $this->initializeQueryBuilder($connection, $parametersGetter);
    }
    private function initializeQueryBuilder(Connection $connection, ParametersHandler $parametersGetter): void
    {
        $class = new ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            if (!$property->getType() instanceof ReflectionNamedType) {
                throw new ReflectionException('Type not found');
            }
            $type = $property->getType()->getName();
            $name = $property->getName();
            if ($type == QueryBuilder::class) {
                $atr = $property->getAttributes(Model::class);
                $value = new ReflectionClass($atr[0]->getArguments()['className']);
                $this->$name = new QueryBuilder($connection, $parametersGetter, $value->newInstanceWithoutConstructor());
            }
        }
    }
}
