<?php

declare(strict_types=1);

namespace MG\Models;

use PDO;

/** Role - statik referans tablosu; RBAC'ın temeli. */
final class Role
{
    public const ADMIN = 'admin';
    public const INSTRUCTOR = 'instructor';
    public const STUDENT = 'student';
    public const GUEST = 'guest';

    /** @return array<int,array{id:int,name:string,description:string}> */
    public static function all(PDO $pdo): array
    {
        return $pdo->query('SELECT id, name, description FROM roles ORDER BY id')->fetchAll();
    }

    public static function findByName(PDO $pdo, string $name): ?array
    {
        $stmt = $pdo->prepare('SELECT id, name, description FROM roles WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row === false ? null : (array)$row;
    }
}
