<?php

namespace App\Model;

class SpecialtyManager extends AbstractManager
{
    const TABLE = 'specialty';

    /**
     * <p>SpecialtyManager constructor.</p>
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * <p>Insert a new specialty.</p>
     * @param object $specialty
     * @return void
     */
    public function insertSpecialty(object $specialty): void
    {
        $query = 'INSERT INTO ' . self::TABLE . ' (title) VALUES (:title)';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':title', $specialty->getProperty('title'), \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * <p>Get the number of specialty.</p>
     * @return int
     */
    public function getTotalNumberOfSpecialties(): int
    {
        $query = 'SELECT count(*) as total FROM ' . self::TABLE;
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $result['total'];
    }

    public function specialtyExists(object $specialty): bool
    {
        $specialty = trim($specialty->getProperty('title'));
        $query     = 'SELECT * FROM ' . self::TABLE . ' WHERE title = :title';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':title', $specialty, \PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll();
        return !empty($results);
    }
}
