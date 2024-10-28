<?php

namespace Nawado\Myorm\Connection;
use PDO;
class Connection
{
    private PDO $pdo;

    public function __construct(string $host, string $port, string $db, string $user, string $password)
    {
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $host,
            $port,
            $db,
            $user,
            $password
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo = $pdo;
    }
    public function connect(): PDO
    {
        return $this->pdo;
    }
}
