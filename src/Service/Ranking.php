<?php

namespace App\Service;

use App\Model\ContestHasChallengeManager;
use App\Model\UserHasContestManager;
use App\Model\UserManager;
use Exception;

class Ranking
{
    /**
     * @param int $contest
     * @return array
     * @throws Exception
     */
    public static function formatRankingContest(int $contest): array
    {
        $playerManager       = new UserHasContestManager();
        $challengesInContest = new ContestHasChallengeManager();
        $ranking             = $playerManager->getContestRanking($contest);
        $nbChallenges        = $challengesInContest->getNumberOfChallengesInContest($contest);
        foreach ($ranking as $userId => $userRanking) {
            $userManager     = new UserManager();
            $user            = $userManager->selectOneById($userId);
            $ranking[$userId]['pseudo']       = $user['pseudo'];
            $ranking[$userId]['total_time']   = ContestDate::getDurationInHoursAndMinutes($userRanking['total_time']);
            $ranking[$userId]['nbChallenges'] = $nbChallenges;
        }

        return $ranking;
    }

    /**
     * @param int $contest
     * @return array
     * @throws Exception
     */
    public static function formatUserRankingInContest(int $contest): array
    {
        $playerManager = new UserHasContestManager();
        $ranking       = $playerManager->getContestRanking($contest);
        $flagsSucceed  = (isset($ranking[$_SESSION['user_id']]['flags_succeed'])) ?
        $ranking[$_SESSION['user_id']]['flags_succeed'] : 0;
        $suffix = ['', 'st', 'nd', 'rd'];
        $userRank = array_search($_SESSION['user_id'], array_keys($ranking)) + 1;
        $rank = ($userRank <= 3) ? $userRank . $suffix[$userRank] : $userRank . 'th';
        $medals = ['', 'gold', 'silver', 'bronze'];
        return [
            'rank' => $rank,
            'medal' => (isset($medals[$userRank]) ? $medals[$userRank] : ''),
            'flags_succeed' => $flagsSucceed,
        ];
    }
}
