<?php

namespace App\Model;

use FormControl\TypeFormControl;

class TypeManager extends AbstractManager
{
    const TABLE = 'type';

    /**
     * <p>TypeManager constructor.</p>
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * <p>Insert a new type.</p>
     * @param TypeFormControl $type
     * @return void
     */
    public function insertType(TypeFormControl $type): void
    {
        $query = 'INSERT INTO ' . self::TABLE . ' (title) VALUES (:title)';

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':title', $type->getProperty('title'), \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * <p>Verify if a type exist.</p>
     * @param TypeFormControl $type
     * @return bool
     */
    public function typeExists(TypeFormControl $type): bool
    {
        $type  = trim($type->getProperty('title'));
        $query = 'SELECT * FROM ' . self::TABLE . ' WHERE title = :title';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':title', $type, \PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll();
        return !empty($results);
    }

    /**
     * <p>Get the number of types.</p>
     * @return int
     */
    public function getTotalNumberOfTypes(): int
    {
        $query = 'SELECT count(*) as total FROM ' . self::TABLE;
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $result['total'];
    }
}
