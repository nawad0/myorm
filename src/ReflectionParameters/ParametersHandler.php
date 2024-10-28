<?php

namespace Nawado\Myorm\ReflectionParameters;

use Nawado\Myorm\Attributes\Key;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class ParametersHandler
{
    private function isBuiltIn(string $name): int|bool
    {
        return preg_match("/^(int|integer|float|double|real|bool|boolean|string|array|object|callable|iterable|null|resource)$/", $name);
    }
    public function getParameters(object $instance): array
    {
        $reflection = new ReflectionClass($instance);
        $params = $reflection->getProperties();
        $output = [];
        foreach ($params as $param) {
            if (!$param->getType() instanceof ReflectionNamedType) {
                throw new ReflectionException('Type not found');
            }
            $name = $param->getType()->getName();
            if ($this->isBuiltIn($name)) {
                $atr = $param->getAttributes(Key::class);
                if (!empty($atr)) {
                    $output[$param->getName()] = 'default';
                } else {
                    $output[$param->getName()] = $param->getDefaultValue() ?? '?';
                }
            } else {
                $className = $this->instantiateClass($name);
                $output[$className] = '?';
            }
        }
        return $output;
    }
    public function getTableName(object $instance): string
    {
        $reflection = new ReflectionClass($instance);
        $tableName = $reflection->getShortName();
        return strtolower($tableName);
    }
    public function setParameters(string $instance, array $data): object
    {
        $reflection = new ReflectionClass($instance);
        $params = $reflection->getProperties();
        $output = [];
        $instance = $reflection->newInstanceWithoutConstructor();
        foreach ($params as $param) {
            if (!$param->getType() instanceof ReflectionNamedType) {
                throw new ReflectionException('Type not found');
            }

            $name = $param->getType()->getName();
            $paramName = $param->getName();

            if ($this->isBuiltIn($name)) {
                $paramNameLower = strtolower($paramName);
                $data[$paramNameLower] ?? $output[$paramName] = $data[$paramNameLower];
                $instance->$paramName = $data[$paramNameLower];
            } else {
            }
        }

        return $instance;
    }
    private function instantiateClass(string $className): string
    {
        if (!class_exists($className)) {
            throw new ReflectionException('Class ' . $className . ' not found');
        }
        return $className;
    }

}