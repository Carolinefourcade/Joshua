<?php

namespace App\Model;

use Exception;

class UserHasContestManager extends AbstractManager
{
    const TABLE = 'user_has_contest';

    /**
     * <p>UserHasContestManager constructor.</p>
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * <p>Verify what state is a challenge compared to a user.</p>
     * @param int $challenge
     * @param int $contest
     * @return string
     */
    public function challengeStatus(int $challenge, int $contest): string
    {
        $query = 'SELECT started_on, ended_on FROM ' . self::TABLE .
            ' WHERE contest_id = :contest AND challenge_id = :challenge AND user_id = ' . $_SESSION['user_id'];
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        $statement->bindValue(':challenge', $challenge, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll();
        if (!empty($result[0])) {
            $result = $result[0];
            if (is_null($result['ended_on']) && !is_null($result['started_on'])) {
                return 'doing';
            } elseif (!is_null($result['ended_on']) && !is_null($result['started_on'])) {
                return 'done';
            } else {
                return 'todo';
            }
        } else {
            return 'todo';
        }
    }

    /**
     * <p>Get how many time put a user to complete a challenge.</p>
     * @param int $challenge
     * @param int $contest
     * @return string|null
     */
    public function challengeTimeToSucceedByUser(int $challenge, int $contest): ?string
    {
        $query = 'SELECT TIMEDIFF(ended_on, started_on) AS time FROM ' . self::TABLE .
            ' WHERE contest_id = :contest AND challenge_id = :challenge AND user_id = ' . $_SESSION['user_id'];
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        $statement->bindValue(':challenge', $challenge, \PDO::PARAM_INT);
        $statement->execute();
        $time = $statement->fetchAll();
        return (!empty($time)) ? $time[0]['time'] : '00:00:00';
    }

    /**
     * <p>Register the success of a user in finishing a challenge.</p>
     * @param int $challenge
     * @param int $contest
     * @throws Exception
     */
    public function registerChallengeSuccess(int $challenge, int $contest): void
    {
        $query = 'UPDATE ' . self::TABLE .
            ' SET ended_on = :now ' .
            ' WHERE user_id = :user AND contest_id = :contest AND challenge_id = :challenge ';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':now', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $statement->bindValue(':user', $_SESSION['user_id'], \PDO::PARAM_INT);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        $statement->bindValue(':challenge', $challenge, \PDO::PARAM_INT);
        if (!$statement->execute()) {
            throw new Exception('Impossible d\'enregistrer votre victoire');
        }
    }

    /**
     * <p>Start a new challenge.</p>
     * @param int $challenge
     * @param int $contest
     */
    public function startNextChallenge(int $challenge, int $contest): void
    {
        $query = 'INSERT INTO ' . self::TABLE . ' (user_id, contest_id, challenge_id, started_on)' .
            ' VALUES (:user, :contest, :challenge, :startdate)';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);
        $statement->bindValue(':challenge', $challenge, \PDO::PARAM_INT);
        $statement->bindValue(':user', $_SESSION['user_id'], \PDO::PARAM_INT);
        $statement->bindValue(':startdate', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * <p> Get the number of challenges played by a user in this contest.</p>
     * @param int $user
     * <p>The user ID.</p>
     * @param int $contest
     * <p>The contest ID.</p>
     * @return int
     * @throws Exception
     */
    public function getNumberFlagsPlayedByUserInContest(int $user, int $contest): ?int
    {
        $query = 'SELECT count(challenge_id) AS challenges_ended FROM ' . self::TABLE .
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
     * <p>The ranking of the contest.</p>
     * @param int $contest
     * <p>The contest ID.</p>
     * @return array|null
     * <ul><li>user_id</li>
     * <li>total_time (the total time used to get the flags)</li>
     * <li>flags_number, total of flags resolved</li></ul>
     * @throws Exception
     */
    public function getContestRanking(int $contest): ?array
    {
        $query = 'SELECT user_id, SUM(ROUND(TIME_TO_SEC(TIMEDIFF(ended_on, started_on))/60)) AS total_time,' .
            ' COUNT(challenge_id) AS flags_succeed' .
            ' FROM ' . self::TABLE .
            ' WHERE contest_id = :contest and ended_on IS NOT NULL' .
            ' GROUP BY user_id ORDER BY flags_succeed DESC, total_time ASC';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contest, \PDO::PARAM_INT);

        $statement->execute();
        $results  = $statement->fetchAll();
        $ranking = [];
        foreach ($results as $user) {
            $ranking[$user['user_id']] = [
                'total_time'    => $user['total_time'],
                'flags_succeed' => $user['flags_succeed'],
            ];
        }
        return $ranking;
    }

    /**
     * @param int $contestId
     * @return int|null
     */
    public function numberOfPlayersInContest(int $contestId): ?int
    {
        $query = 'SELECT count(distinct user_id) as total FROM ' . self::TABLE . ' WHERE contest_id = :contest';
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':contest', $contestId, \PDO::PARAM_INT);
        $statement->execute();
        $results   = $statement->fetch();
        return $results['total'];
    }
}
