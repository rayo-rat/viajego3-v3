<?php

class MySQLSessionHandler extends SessionHandler
{
    private $pdo;
    private $ttl;

    public function __construct($pdo, $ttl = 3600)
    {
        $this->pdo = $pdo;
        $this->ttl = $ttl;
        $this->crearTabla();
    }

    private function crearTabla()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sesiones (
                id VARCHAR(255) PRIMARY KEY,
                datos LONGTEXT NOT NULL,
                expiracion INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->pdo->exec($sql);
    }

    public function read(string $id): string
    {
        $sql = "SELECT datos FROM sesiones 
                WHERE id = :id AND expiracion > :time 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':time' => time()
        ]);

        $fila = $stmt->fetch();
        return $fila ? $fila['datos'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $sql = "
            REPLACE INTO sesiones (id, datos, expiracion)
            VALUES (:id, :datos, :exp)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':datos' => $data,
            ':exp' => time() + $this->ttl
        ]);
    }

    public function destroy(string $id): bool
    {
        $sql = "DELETE FROM sesiones WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $sql = "DELETE FROM sesiones WHERE expiracion < :time";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':time' => time()]);

        return $stmt->rowCount(); // PHP exige int|false
    }
}

