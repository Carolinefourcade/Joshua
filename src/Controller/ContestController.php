<?php


namespace App\Controller;

use App\Model\ChallengeManager;
use App\Model\ContestHasChallengeManager;
use App\Model\ContestManager;
use App\Model\StoryManager;
use App\Model\UserHasContestManager;
use App\Service\ContestDate;
use App\Service\ContestService;
use App\Service\Dispatch;
use App\Service\Ranking;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ContestController extends AbstractController
{
    /**
     * @param int $contest
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function play(int $contest)
    {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id']!='') {
            $contestService   = new ContestService();
            $contestManager   = new ContestManager();
            $challengeManager = new ChallengeManager();

            $theContest = $contestManager->selectOneById($contest);
            $endDate    = ContestDate::getContestEndDate($theContest['started_on'], $theContest['duration']);

            if ($theContest && !empty($endDate) && !ContestDate::isEnded($endDate)) {
                $challengesList    = $contestService->listChallengesWithSuccess($contest);
                $challengeOnTheWay = $challengeManager->challengeOnTheWayByUser($contest);

                if (!$challengeOnTheWay) {
                    $challengeOnTheWay = $contestService->startFirstChallenge($contest);
                }

                return $this->twig->render('Contests/play.html.twig', [
                    'contest'           => $theContest,
                    'github'            => $_SESSION['github'],
                    'challenges'        => $challengesList,
                    'challengeOnTheWay' => $challengeOnTheWay,
                    'difficulty'        => $contestService->difficulties($challengeOnTheWay['difficulty']),
                    'ended'             => false,
                    'open'              => true,
                    'end_date'          => $endDate,
                    'rank_users'        => Ranking::formatRankingContest($contest),
                ]);
            } else {
                Dispatch::toUrl('/');
            }
        } else {
            Dispatch::toUrl('/');
        }
    }

    /**
     * @param int $contest
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function results(int $contest)
    {
        $contestManager = new ContestManager();
        $theContest     = $contestManager->selectOneById($contest);

        if ($theContest && !is_null($theContest['started_on'])) {
            $endDate = ContestDate::getContestEndDate($theContest['started_on'], $theContest['duration']);
            $ranking = Ranking::formatRankingContest($contest);
            return $this->twig->render('Contests/results.html.twig', [
                'contest'  => $theContest,
                'ended'    => ContestDate::isEnded($endDate),
                'end_date' => $endDate,
                'ranking'  => $ranking,
            ]);
        } else {
            Dispatch::toUrl('/joshua/page404');
        }
    }

    /**
     * @return false|string
     * @throws Exception
     */
    public function sendSolution()
    {
        $data = file_get_contents('php://input');
        $json = json_decode($data);

        if (ContestService::isSolutionPossible($json->contest_id)) {
            $challengeManager  = new ChallengeManager();
            $solutionUsed      = $json->flagSolution;
            $challengeSolution = $challengeManager->getChallengeSolution($json->challenge_id);
            $return            = [];
            $storyManager      = new StoryManager();

            if ($solutionUsed === $challengeSolution) {
                $playerManager = new UserHasContestManager();
                $playerManager->registerChallengeSuccess($json->challenge_id, $json->contest_id);
                $nextFlagOrder = $json->challenge_order + 1;
                $challengesInContest = new ContestHasChallengeManager();
                $nextChallenge = $challengesInContest->getNextChallengeToPlay($nextFlagOrder, $json->contest_id);

                if ($nextChallenge) {
                    $playerManager->startNextChallenge($nextChallenge, $json->contest_id);
                    $return['message'] = 'success';
                } else {
                    $return['message'] = 'end';
                }
                $storyManager->setHistory($_SESSION['user_id'], $json->contest_id, $json->challenge_id, 1);
            } else {
                $return['message'] = 'error';
                $storyManager->setHistory($_SESSION['user_id'], $json->contest_id, $json->challenge_id, 0);
            }
            return json_encode($return);
        } else {
            Dispatch::toUrl('/');
        }
    }

    /**
     * @param int $contestId
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getRankingInContest(int $contestId)
    {
        $contestManager = new ContestManager();
        $contest        = $contestManager->selectOneById($contestId);

        if ($contest) {
            return $this->twig->render('Components/_ranking.html.twig', [
                'rank_users' => Ranking::formatRankingContest($contestId),
            ]);
        }
    }

    /**
     * @param int $contestId
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getHistoryOfContest(int $contestId)
    {
        $contestId    = intval($contestId);
        $storyManager = new StoryManager();
        $postSolution = $storyManager->getHistory($contestId);
        return $this->twig->render('Components/_console.html.twig', [
            'solutions' => $postSolution,
        ]);
    }
}
