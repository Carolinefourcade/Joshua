<?php


namespace App\Controller;

use App\Model\CampusManager;
use App\Model\ContestHasChallengeManager;
use App\Model\ContestManager;
use App\Model\SpecialtyManager;
use App\Model\UserManager;
use App\Service\Dispatch;
use App\Service\Ranking;
use App\Service\UserService;
use Exception;
use FormControl\UserEditFormControl;
use FormControl\UserFormControl;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserController extends AbstractController
{
    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function register()
    {
        $campuses        = new CampusManager();
        $campusesList    = $campuses->selectAll();
        $specialties     = new SpecialtyManager();
        $specialtiesList = $specialties->selectAll();

        return $this->twig->render('User/register.html.twig', [
            'campuses'    => $campusesList,
            'specialties' => $specialtiesList,
        ]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function insertUser()
    {
        if (count($_POST) > 0 && isset($_POST['registerUser'])) {
            $user = new UserFormControl($_POST);

            if (count($user->getErrors()) === 0) {
                $newUser = new UserManager();
                $idUser = $newUser->addUser($user);
                UserService::openConnection($idUser);
                Dispatch::toUrl('/user/confirmuser/' . $idUser);
            } else {
                $campuses        = new CampusManager();
                $campusesList    = $campuses->selectAll();
                $specialties     = new SpecialtyManager();
                $specialtiesList = $specialties->selectAll();

                return $this->twig->render('User/register.html.twig', [
                    'errors'      => $user->getErrors(),
                    'user'        => $user->getAllProperty(),
                    'campuses'    => $campusesList,
                    'specialties' => $specialtiesList,
                ]);
            }
        } else {
            Dispatch::toUrl('/');
        }
    }

    /**
     * @param int $idUser
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function confirmUser(int $idUser)
    {
        $user        = new UserManager();
        $userCreated = $user->selectOneById($idUser);

        return $this->twig->render('User/user_confirm.html.twig', ['user' => $userCreated]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function profile()
    {
        $userId         = $_SESSION['user_id'];
        $contestManager = new ContestManager();
        $userContests   = $contestManager->getContestsPlayedByUser($userId, 5);

        $challengesInContest = new ContestHasChallengeManager();
        $limit = count($userContests);
        for ($i = 0; $i < $limit; $i++) {
            $contestId = $userContests[$i]['id'];
            $ranking = Ranking::formatUserRankingInContest($contestId);
            $userContests[$i]['resume'] = [
                'started_on'           => $contestManager->getUserContestStartTime($userId, $contestId),
                'challenges_played'    => $ranking['flags_succeed'],
                'number_of_challenges' => $challengesInContest->getNumberOfChallengesInContest($contestId),
                'user_rank'            => $ranking['rank'],
                'medal'                => $ranking['medal'],
            ];
        }

        $user        = new UserManager();
        $userCreated = $user->selectOneById($userId);

        return $this->twig->render('User/user_profile.html.twig', [
            'user'     => $userCreated,
            'contests' => $userContests,
        ]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function edit()
    {
        $userManager     = new UserManager();
        $userInfos       = $userManager->selectOneById($_SESSION['user_id']);
        $campuses        = new CampusManager();
        $campusesList    = $campuses->selectAll();
        $specialties     = new SpecialtyManager();
        $specialtiesList = $specialties->selectAll();
        $errors          = [];

        if (count($_POST) > 0 && isset($_POST['updateUser'])) {
            $user = new UserEditFormControl($_POST);
            $errors = $user->getErrors();

            if (count($errors) === 0) {
                $userManager->updateUser($user);
                UserService::openConnection($_SESSION['user_id']);
                Dispatch::toUrl('/user/profile/' . $_SESSION['user_id']);
            }
        }

        return $this->twig->render('User/user_edit.html.twig', [
            'errors'      => $errors,
            'user'        => $userInfos,
            'campuses'    => $campusesList,
            'specialties' => $specialtiesList,
        ]);
    }

    public static function logOut()
    {
        $_SESSION = array();
        session_destroy();
        unset($_SESSION);
        Dispatch::toUrl('/');
    }
}
