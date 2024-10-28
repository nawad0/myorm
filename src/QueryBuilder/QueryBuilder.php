<?php

namespace Nawado\Myorm\QueryBuilder;

use Exception;
use Nawado\Myorm\ReflectionParameters\ParametersHandler;
use Nawado\Myorm\Connection\Connection;
use PDO;
use ReflectionException;

class QueryBuilder
{
    private PDO $pdo;
    private ParametersHandler $parametersHandler;
    private object $model;
    private string $query = '';

    /** @var array<mixed> $bindings */
    private array $bindings = [];

    public function __construct(Connection $connection, ParametersHandler $parametersGetter, object $model)
    {
        $this->model = $model;
        $this->parametersHandler = $parametersGetter;
        $this->pdo = $connection->connect();
    }

    /**
     * @param array<object> $data
     * @return void
     * @throws Exception
     */
    public function batchInsert(array $data): void
    {
        try {
            $parameters = $this->parametersHandler->getParameters($this->model);
            $tableName = $this->parametersHandler->getTableName($this->model);
        } catch (ReflectionException $e) {
            throw new Exception($e->getMessage());
        }
        $values = sprintf('(%s),', implode(',', array_values($parameters)));

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $tableName,
            implode(',', array_keys($parameters)),
            rtrim(str_repeat($values, count($data)), ',')
        );

        $stmt = $this->pdo->prepare($sql);
        $values = [];
        foreach ($data as $model) {
            $values = array_map(fn ($value) => (string)$value, array_merge($values, array_values(get_object_vars($model))));
        }
        $stmt->execute($values);
    }

    /**
     * @param array<string> $columns
     * @return $this
     */
    public function select(array $columns = ['*']): self
    {
        $this->query = sprintf(
            "SELECT %s FROM %s",
            implode(', ', $columns),
            $this->parametersHandler->getTableName($this->model)
        );
        return $this;
    }

    private function addCondition(string $type, string $column, string $operator, string|null $value): self
    {
        if (empty($value)) {
            return $this;
        }

        if (stripos($this->query, 'WHERE') === false) {
            $condition = sprintf(" WHERE %s %s ?", $column, $operator);
        } else {
            $condition = sprintf(" %s %s %s ?", $type, $column, $operator);
        }

        $this->query .= $condition;
        $this->bindings[] = $value;

        return $this;
    }

    public function between(string $column, string $start, string $end): self
    {
        $condition = sprintf("( %s BETWEEN ? AND ?)", $column);

        if (stripos($this->query, 'WHERE') === false) {
            $this->query .= " WHERE " . $condition;
        } else {
            $this->query .= " AND " . $condition;
        }

        $this->bindings[] = $start;
        $this->bindings[] = $end;

        return $this;
    }

    public function where(string $column, string $operator, string|null $value): self
    {
        return $this->addCondition('', $column, $operator, $value);
    }

    public function orWhere(string $column, string $operator, string|null $value): self
    {
        return $this->addCondition('OR', $column, $operator, $value);
    }

    public function andWhere(string $column, string $operator, string|null $value): self
    {
        return $this->addCondition('AND', $column, $operator, $value);
    }

    public function orderBy(string $column, string $direction): self
    {
        if (stripos($this->query, 'ORDER') === false) {
            $condition = sprintf(" ORDER BY %s %s", $column, $direction);
        } else {
            $condition = sprintf(", %s %s", $column, $direction);
        }
        $this->query .= $condition;
        return $this;
    }

    public function startBracket(): self
    {
        $this->query .= " (";
        return $this;
    }

    public function endBracket(): self
    {
        $this->query .= " )";
        return $this;
    }

    public function and(): self
    {
        $this->query .= " AND";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query .= sprintf(" LIMIT %d", $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query .= sprintf(" OFFSET %d", $offset);
        return $this;
    }
    public function truncate(): void
    {
        $truncateQuery = "TRUNCATE TABLE " . $this->parametersHandler->getTableName($this->model)." RESTART IDENTITY";
        $statement = $this->pdo->prepare($truncateQuery);
        $statement->execute();
    }

    /**
     * @return array<mixed>
     * @throws Exception
     */
    public function get(): array
    {
        $statement = $this->pdo->prepare($this->query);
        $statement->execute($this->bindings);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $modelClass = get_class($this->model);
        try {
            $modelsArray = array_map(function ($data) use ($modelClass) {
                return $this->parametersHandler->setParameters($modelClass, $data);
            }, $results);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $modelsArray;
    }
    public function count(): int
    {
        $statement = $this->pdo->prepare($this->query);
        $statement->execute($this->bindings);
        return $statement->rowCount();
    }
}
