<?php

namespace App\Model;

use \Exception;

class ContestManager extends AbstractManager
{
    const TABLE = 'contest';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * @param object $contest
     */
    public function addContest(object $contest): void
    {
        $query = 'INSERT INTO ' . self::TABLE . ' (name, campus_id, description, duration, image)' .
            ' VALUES (:name, :campus, :description, :duration, :image)';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':name', $contest->getProperty('name'), \PDO::PARAM_STR);
        $statement->bindValue(':campus', $contest->getProperty('campus'), \PDO::PARAM_INT);
        $statement->bindValue(':description', $contest->getProperty('description'), \PDO::PARAM_STR);
        $statement->bindValue(':duration', $contest->getProperty('duration'), \PDO::PARAM_INT);
        $statement->bindValue(':image', $contest->getProperty('image'), \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * @param object $contest
     * @param int $id
     * @return int
     */
    public function editContest(object $contest, int $id): int
    {
        $query = 'UPDATE ' . self::TABLE . ' SET name = :name, campus_id = :campus, description = :description,' .
            ' duration = :duration, image = :image' .
            ' WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->bindValue(':name', $contest->getProperty('name'), \PDO::PARAM_STR);
        $statement->bindValue(':campus', $contest->getProperty('campus'), \PDO::PARAM_INT);
        $statement->bindValue(':description', $contest->getProperty('description'), \PDO::PARAM_STR);
        $statement->bindValue(':duration', $contest->getProperty('duration'), \PDO::PARAM_INT);
        $statement->bindValue(':image', $contest->getProperty('image'), \PDO::PARAM_STR);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * @param int $id
     */
    public function deleteContest(int $id): void
    {
        $query = 'DELETE FROM ' . self::TABLE . ' WHERE id = ' . $id;
        $statement = $this->pdo->prepare($query);
        $statement->execute();
    }

    /**
     * @return array
     */
    public function getVisibleContests(): array
    {
        $query = 'SELECT c.id, c.is_active AS active, c.name, c.image, c.description, ca.city AS campus , c.duration,' .
            ' c.started_on AS beginning FROM ' . self::TABLE . ' c' .
            ' LEFT JOIN ' . CampusManager::TABLE . ' ca ON ca.id = c.campus_id' .
            ' WHERE c.is_visible = 1';

        return $this->pdo->query($query)->fetchAll();
    }

    /**
     * The User ID
     * @var int $limit
     * The list of the contests played by user
     * @var int $user
     * The number of results you need. If empty, return all results
     * @return array
     */
    public function getContestsPlayedByUser(int $user, int $limit = 0): array
    {
        $query = 'SELECT distinct c.id, c.name  FROM ' . self::TABLE . ' c ' .
            ' JOIN ' . UserHasContestManager::TABLE . ' uhc ON ' .
            ' uhc.contest_id = c.id ' .
            ' WHERE uhc.user_id = :user ' .
            ' ORDER BY c.name ';
        if ($limit != 0) {
            $query .= ' LIMIT ' . $limit;
        }
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':user', $user, \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * The user ID
     * @param int $user
     * The contest ID
     * @param int $contest
     * The date and time aaaa-mm-jj H:i:s when the user started his first challenge in this contest
     * @return string
     * @throws Exception
     */
    public function getUserContestStartTime(int $user, int $contest): ?string
    {
        $query = 'SELECT started_on FROM ' . UserHasContestManager::TABLE .
            ' WHERE user_id = :user AND contest_id = :contest ORDER BY started_on LIMIT 1';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':user', $user, \PDO::PARAM_INT);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);

        if ($statement->execute()) {
            $result = $statement->fetch();
            return $result['started_on'];
        } else {
            throw new Exception('Impossible to get the started date');
        }
    }

    /**
     * the user ID
     * @param int $user
     * the contest ID
     * @param int $contest
     * The number of challenges played by user in this contest
     * @return int
     * @throws Exception
     */
    public function getNumberFlagsPlayedByUserInContest(int $user, int $contest): ?int
    {
        $query = 'SELECT count(challenge_id) AS challenges_ended FROM ' . UserHasContestManager::TABLE .
            ' WHERE user_id = :user AND contest_id = :contest AND ended_on IS NOT NULL ';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':user', $user, \PDO::PARAM_INT);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);

        if ($statement->execute()) {
            $result = $statement->fetch();
            return $result['challenges_ended'];
        } else {
            throw new Exception('Impossible to get the number of played challenges');
        }
    }

    /**
     * The contest ID
     * @param int $contest
     * The number of challenges in this contest
     * @return int
     * @throws Exception
     */
    public function getNumberOfChallengesInContest(int $contest): ?int
    {
        $query = 'SELECT count(challenge_id) as total_challenges' .
            ' FROM ' . ContestHasChallengeManager::TABLE . ' WHERE contest_id = :contest';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        if ($statement->execute()) {
            $result = $statement->fetch();
            return $result['total_challenges'];
        } else {
            throw new Exception('Impossible to get the number of challenges in this contest');
        }
    }

    /**
     * the contest ID
     * @param int $contest
     * The palmares of the contest.
     * Returns user_id
     * and total_time (the total time used to get the flags)
     * and flags_number, total of flags resolved
     * @return array|null
     * @throws Exception
     */
    public function getContestPalmares(int $contest): ?array
    {
        $query = 'SELECT user_id, SUM(TIMEDIFF(ended_on, started_on)) AS total_time,' .
            ' COUNT(challenge_id) AS flags_succeed' .
            ' FROM ' . UserHasContestManager::TABLE . ' WHERE contest_id = :contest and ended_on IS NOT NULL' .
            ' GROUP BY user_id ORDER BY flags_succeed DESC, total_time ASC';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        $statement->execute();
        $results  = $statement->fetchAll();
        $palmares = [];
        foreach ($results as $user) {
            $palmares[$user['user_id']] = [
                'total_time'    => $user['total_time'],
                'flags_succeed' => $user['flags_succeed'],
            ];
        }
        return $palmares;
    }

    /**
     * @param string $contestId
     */
    public function setContestActive(string $contestId): void
    {
        $query = 'UPDATE ' . self::TABLE . ' SET is_active = 1, started_on = now() WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $contestId, \PDO::PARAM_INT);
        $statement->execute();
    }
}
