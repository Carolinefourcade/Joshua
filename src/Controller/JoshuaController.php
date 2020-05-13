<?php

namespace App\Controller;

use App\Model\ContestManager;
use App\Model\UserManager;
use App\Service\ContestDate;
use App\Service\Dispatch;
use App\Service\UserService;
use Exception;
use FormControl\IndexFormControl;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class JoshuaController extends AbstractController
{
    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index()
    {
        $login = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $connectUser = new IndexFormControl($_POST);
            $login       = $connectUser->getProperty('pseudo');
            $password    = $connectUser->getProperty('password');

            if (count($connectUser->getErrors()) === 0) {
                $userManager = new UserManager();
                $user        = $userManager->selectOneByPseudo($login);

                if ($user && $login === $user['pseudo'] && $user['is_active']) {
                    if (password_verify($password, $user['password'])) {
                        UserService::openConnection($user['id']);
                        Dispatch::toUrl('/joshua/home');
                    } else {
                        $error = 'Invalid password !';
                    }
                } else {
                    if ($user && !$user['is_active']) {
                        $error = 'This account is inactive !';
                    } else {
                        $error = 'This pseudo doesn\'t exist !';
                    }
                }
            }
        }

        return $this->twig->render('Home/index.html.twig', [
            'login' => $login,
            'error' => $error,
        ]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function home()
    {
        $contestManager  = new ContestManager();
        $visibleContests = $contestManager->selectAll(ContestManager::NOT_ENDED, ContestManager::ONLY_VISIBLE);
        $nbContestsEnded = $contestManager->getTotalNumberOfContestEnded();

        $nbContests = count($visibleContests);
        for ($i = 0; $i < $nbContests; $i++) {
            $visibleContests[$i]['is_active'] = (bool)$visibleContests[$i]['is_active'];

            $beginning = $visibleContests[$i]['started_on'];
            $duration  = $visibleContests[$i]['duration'];

            $formattedDuration = ContestDate::getDurationInHoursAndMinutes($duration, ContestDate::ARRAY);
            $visibleContests[$i]['formatted_duration'] = $formattedDuration;
            $visibleContests[$i]['end_date'] = ContestDate::getContestEndDate($beginning, $duration);
        }

        return $this->twig->render('Home/home.html.twig', [
            'contests'   => $visibleContests,
            'nb_ended'   => $nbContestsEnded,
            'have_ended' => $nbContestsEnded > 0,
        ]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function oldContests()
    {
        $contestManager = new ContestManager();
        $oldContests    = $contestManager->selectAll(ContestManager::ENDED);
        $nbContests     = count($oldContests);

        for ($i = 0; $i < $nbContests; $i++) {
            $duration          = $oldContests[$i]['duration'];
            $formattedDuration = ContestDate::getDurationInHoursAndMinutes($duration, ContestDate::ARRAY);
            $oldContests[$i]['formatted_duration'] = $formattedDuration;
        }

        return $this->twig->render('Home/old_contests.html.twig', ['contests' => $oldContests]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function about()
    {
        return $this->twig->render('Home/about.html.twig');
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function page404()
    {
        return $this->twig->render('404.html.twig', []);
    }
}
