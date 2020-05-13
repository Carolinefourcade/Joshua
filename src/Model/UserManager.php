<?php

namespace App\Model;

use App\Service\UserPaginator;
use Exception;
use FormControl\UserEditFormControl;
use FormControl\UserFormControl;

class UserManager extends AbstractManager
{
    const TABLE = 'user';
    const NOT_ADMIN = false;
    const ADMIN = true;
    const NOT_ACTIVE = false;
    const ACTIVE = true;
    const NB_OF_LAST_REGISTER_USER = 3;

    /**
     * <p>UserManager constructor.</p>
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * <p>Select one user by a id, add campus and specialty.</p>
     * @param int $id
     * @return array
     */
    public function selectOneById(int $id): array
    {
        $query = 'SELECT u.id, is_admin, is_active, u.lastname, u.firstname, u.pseudo, u.github, u.email, u.password,'
            . ' u.specialty_id, s.title AS specialty, u.campus_id, c.city AS campus, u.created_on, u.updated_on'
            . ' FROM ' . self::TABLE . ' u'
            . ' JOIN ' . SpecialtyManager::TABLE . ' s ON s.id = u.specialty_id'
            . ' JOIN ' . CampusManager::TABLE . ' c ON c.id = u.campus_id'
            . ' WHERE u.id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id, \PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetch();
    }

    /**
     * <p>Insert a new user.</p>
     * @param UserFormControl $data
     * @return int
     * @throws Exception
     */
    public function addUser(UserFormControl $data): int
    {
        $query  = 'INSERT INTO ' . self::TABLE .
            ' (lastname, firstname, pseudo, github, email, password, specialty_id, campus_id)' .
            ' VALUES (:lastname, :firstname, :pseudo, :github, :email, :password, :specialty, :campus)';

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':lastname', $data->getProperty('lastname'), \PDO::PARAM_STR);
        $statement->bindValue(':firstname', $data->getProperty('firstname'), \PDO::PARAM_STR);
        $statement->bindValue(':pseudo', $data->getProperty('pseudo'), \PDO::PARAM_STR);
        $statement->bindValue(':github', $data->getProperty('pseudoGithub'), \PDO::PARAM_STR);
        $statement->bindValue(':email', $data->getProperty('email'), \PDO::PARAM_STR);
        $password = password_hash($data->getProperty('password'), PASSWORD_BCRYPT);
        $statement->bindValue(':password', $password, \PDO::PARAM_STR);
        $statement->bindValue(':specialty', $data->getProperty('specialty'), \PDO::PARAM_INT);
        $statement->bindValue(':campus', $data->getProperty('campus'), \PDO::PARAM_INT);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        } else {
            throw new Exception('Unable to add user');
        }
    }

    /**
     * <p>Edit a existing user.</p>
     * @param UserEditFormControl $data
     */
    public function updateUser(UserEditFormControl $data): void
    {
        $query  = 'UPDATE ' . self::TABLE .
            ' set lastname = :lastname, firstname = :firstname, pseudo = :pseudo, github = :github, email = :email,';
        if ($data->getProperty('password') != null) {
            $query .= ' password = :password, ';
        }
        $query .= ' specialty_id = :specialty, campus_id = :campus, updated_on = now()' .
            ' WHERE id = ' . $_SESSION['user_id'];

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':lastname', $data->getProperty('lastname'), \PDO::PARAM_STR);
        $statement->bindValue(':firstname', $data->getProperty('firstname'), \PDO::PARAM_STR);
        $statement->bindValue(':pseudo', $data->getProperty('pseudo'), \PDO::PARAM_STR);
        $statement->bindValue(':github', $data->getProperty('pseudoGithub'), \PDO::PARAM_STR);
        $statement->bindValue(':email', $data->getProperty('email'), \PDO::PARAM_STR);
        if ($data->getProperty('password') != null) {
            $password = password_hash($data->getProperty('password'), PASSWORD_BCRYPT);
            $statement->bindValue(':password', $password, \PDO::PARAM_STR);
        }
        $statement->bindValue(':specialty', $data->getProperty('specialty'), \PDO::PARAM_INT);
        $statement->bindValue(':campus', $data->getProperty('campus'), \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * <p>Select a user compared to a pseudo.</p>
     * @param string $pseudo
     * @return mixed
     */
    public function selectOneByPseudo(string $pseudo)
    {
        $query     = 'SELECT * FROM ' . self::TABLE . ' WHERE pseudo = :pseudo';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':pseudo', $pseudo, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch();
        }
    }

    /**
     * <p>Select a user compared to an email.</p>
     * @param string $eMail
     * @return mixed
     */
    public function selectOneByEmail(string $eMail)
    {
        $query     = 'SELECT * FROM ' . self::TABLE . ' WHERE email = :email';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':email', $eMail, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch();
        } else {
            return false;
        }
    }


    /**
     * @return array
     */
    public function getLastRegisterUsers(): array
    {
        $query = 'SELECT id, pseudo, created_on FROM ' . self::TABLE .
            ' ORDER BY created_on DESC LIMIT ' . self::NB_OF_LAST_REGISTER_USER;
        $statement = $this->pdo->query($query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * <p>Get the number of user.</p>
     * @param int $excluded [optional]<br>
     * @return int
     */
    public function getTotalUsers($excluded = 0): int
    {
        $query     = 'SELECT count(id) AS total FROM ' . self::TABLE;
        if ($excluded != 0) {
            $query .= ' WHERE id != ' . $excluded;
        }
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $results   = $statement->fetchAll();
        return $results[0]['total'];
    }

    /**
     * @param string $orderBy
     * <p>Field to sort on</p>
     * @param string $sortOrder
     * <p>Sort order : ASC or DESC</p>
     * @param int $page [optional]<br>
     * @param int $excluded [optional]<br>
     * @return array
     */
    public function selectAllOrderBy(string $orderBy, string $sortOrder, int $page = 1, $excluded = 0): array
    {
        $offset = ($page-1) * UserPaginator::LIMIT_LIST_USERS;
        $query  = 'SELECT * FROM ' . self::TABLE ;
        if ($excluded != 0) {
            $query .= ' WHERE id != ' . $excluded;
        }
        $query .=   ' ORDER BY ' . $orderBy . ' ' . $sortOrder .
            ' LIMIT ' . UserPaginator::LIMIT_LIST_USERS . ' OFFSET ' . $offset;
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * <p>Set a user admin or not.</p>
     * @param bool $status
     * <p>Status of user :</p>
     * <ul><li>Set user admin : <b>UserManager::ADMIN</b></li>
     * <li>Set user not admin : <b>UserManager::NOT_ADMIN</b></li></ul>
     * @param int $user
     * <p>The user ID</p>
     */
    public function userSetAdmin(bool $status, int $user): void
    {
        $query = 'UPDATE ' . self::TABLE . ' SET is_admin = :status WHERE id = :user';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':status', $status, \PDO::PARAM_INT);
        $statement->bindValue(':user', $user, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * <p>Set a user active or not.</p>
     * @param bool $status
     * <p>Status of user :</p>
     * <ul><li>Set user active : <b>UserManager::ACTIVE</b></li>
     * <li>Set user not active : <b>UserManager::NOT_ACTIVE</b></li></ul>
     * @param int $user
     */
    public function userSetActive(bool $status, int $user): void
    {
        $query = 'UPDATE ' . self::TABLE . ' SET is_active = :status WHERE id = :user';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':status', $status, \PDO::PARAM_INT);
        $statement->bindValue(':user', $user, \PDO::PARAM_INT);
        $statement->execute();
    }
}
