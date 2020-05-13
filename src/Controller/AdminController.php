<?php

namespace App\Controller;

use App\Model\CampusManager;
use App\Model\ChallengeManager;
use App\Model\ContestHasChallengeManager;
use App\Model\ContestManager;
use App\Model\DifficultyManager;
use App\Model\SpecialtyManager;
use App\Model\TypeManager;
use App\Model\UserManager;
use App\Service\ContestDate;
use App\Service\Dispatch;
use App\Service\UserPaginator;
use Exception;
use FormControl\CampusFormControl;
use FormControl\ChallengeFormControl;
use FormControl\ContestFormControl;
use FormControl\SpecialtyFormControl;
use FormControl\TypeFormControl;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AdminController extends AbstractController
{
    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function index()
    {
        $challengeManager   = new ChallengeManager();
        $challenges         = $challengeManager->selectAll();
        $numberOfChallenges = count($challenges);

        $contestManager       = new ContestManager();
        $contests             = $contestManager->selectAll(ContestManager::NOT_STARTED);
        $nbOfContestsNotEnded = $contestManager->getTotalNumberOfContestNotEnded();
        $nbOfContestsEnded    = $contestManager->getTotalNumberOfContestEnded();
        $activeContests       = $contestManager->selectAll(ContestManager::STARTED);
        $activeContests       = ContestDate::getContestsEndDateInArray($activeContests);

        $userManager   = new UserManager();
        $totalUsers    = $userManager->getTotalUsers();
        $lastRegisters = $userManager->getLastRegisterUsers();

        $campusManager = new CampusManager();
        $totalCampuses = $campusManager->getTotalNumberOfCampus();

        $specialtyManager  = new SpecialtyManager();
        $totalSpecialties  = $specialtyManager->getTotalNumberOfSpecialties();

        return $this->twig->render('Admin/admin.html.twig', [
            'total_challenges'      => $numberOfChallenges,
            'challenges'            => $challenges,
            'contests'              => $contests,
            'nb_contests_not_ended' => $nbOfContestsNotEnded,
            'nb_contests_ended'     => $nbOfContestsEnded,
            'active_contests'       => $activeContests,
            'total_users'           => $totalUsers,
            'last_registers'        => $lastRegisters,
            'total_campuses'        => $totalCampuses,
            'total_specialties'     => $totalSpecialties,
        ]);
    }

    // CHALLENGE

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function manageChallenge()
    {
        $challenges     = new ChallengeManager();
        $challengesList = $challenges->selectAll();

        $difficulties     = new DifficultyManager();
        $difficultiesList = $difficulties->selectAll();

        $types     = new TypeManager();
        $typesList = $types->selectAll();

        $challenge = null;

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['createChallenge'])) {
            $challenge = new ChallengeFormControl($_POST);
            $errors    = $challenge->getErrors();
            if (count($errors) === 0) {
                $challengeManager = new ChallengeManager();
                $challengeManager->addChallenge($challenge);
                Dispatch::toUrl('/admin/managechallenge');
            }
        }

        return $this->twig->render('Admin/challenge.html.twig', [
            'challenges'   => $challengesList,
            'difficulties' => $difficultiesList,
            'types'        => $typesList,
            'challenge'    => $challenge,
        ]);
    }

    /**
     * @param int $id
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function editChallenge(int $id)
    {
        $difficulties     = new DifficultyManager();
        $difficultiesList = $difficulties->selectAll();

        $types     = new TypeManager();
        $typesList = $types->selectAll();

        $challenge      = new ChallengeManager();
        $challengeEdit  = $challenge->selectOneById($id);

        $onContest      = new ContestHasChallengeManager();
        $onContestCheck = $onContest->selectChallengeInContestById($id);

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['saveChallenge'])) {
            $challenge = new ChallengeFormControl($_POST);
            $errors    = $challenge->getErrors();

            if (count($errors) === 0) {
                $challengeManager = new ChallengeManager();
                $challengeManager->editChallenge($challenge, $id);
                Dispatch::toUrl('/admin/managechallenge');
            }
        }

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['deleteChallenge'])) {
            $challengeManager = new ChallengeManager();
            $challengeManager->deleteChallenge($id);
            Dispatch::toUrl('/admin/managechallenge');
        }

        return $this->twig->render('Admin/challenge_edit.html.twig', [
            'challenge'    => $challengeEdit,
            'difficulties' => $difficultiesList,
            'types'        => $typesList,
            'onContest'    => $onContestCheck
        ]);
    }

    // CONTEST

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function manageContest()
    {
        $campuses     = new CampusManager();
        $campusesList = $campuses->selectAll();

        $contests     = new ContestManager();
        $contestsList = $contests->selectAll(ContestManager::NOT_ENDED);

        $contest      = null;

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['createBlankContest'])) {
            $contest = new ContestFormControl($_POST);
            $errors  = $contest->getErrors();
            if (count($errors) === 0) {
                $contestManager = new ContestManager();
                $contestManager->addContest($contest);
                Dispatch::toUrl('/admin/managecontest');
            }
        }

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['createFullContest'])) {
            $contest = new ContestFormControl($_POST);
            $errors  = $contest->getErrors();
            if (count($errors) === 0) {
                $contestManager = new ContestManager();
                $contestId      = $contestManager->addContest($contest);
                Dispatch::toUrl('/admin/editcontest/' . $contestId);
            }
        }

        return $this->twig->render('Admin/contest.html.twig', [
            'campuses' => $campusesList,
            'contests' => $contestsList,
            'contest'  => $contest
        ]);
    }

    /**
     * @param int $id
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function editContest(int $id)
    {
        $campuses     = new CampusManager();
        $campusesList = $campuses->selectAll();

        $challenges     = new ChallengeManager();
        $challengesList = $challenges->selectChallengesNotInContest($id);

        $contest      = new ContestManager();
        $contestEdit  = $contest->selectOneById($id);

        $challengesInContest      = new ContestHasChallengeManager();
        $challengesInContestEdit  = $challengesInContest->selectChallengesByContestId($id);

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['saveContest'])) {
            $contest = new ContestFormControl($_POST);
            $errors  = $contest->getErrors();
            $challengeOrder = json_decode($_POST['orderOfChallenges'], true);
            if (count($errors) === 0) {
                $contestManager = new ContestManager();
                $contestManager->editContest($contest, $id);
                if (!empty($_POST['orderOfChallenges'])) {
                    $challengeManager = new ContestHasChallengeManager();
                    $challengeManager->deleteChallengesInContest($id);
                    $challengeManager->addChallengesInContest($challengeOrder, $id);
                }

                Dispatch::toUrl('/admin/managecontest');
            }
        }

        if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['deleteContest'])) {
            $contestManager = new ContestManager();
            $contestManager->deleteContest($id);
            Dispatch::toUrl('/admin/managecontest');
        }

        return $this->twig->render('Admin/contest_edit.html.twig', [
            'campuses'   => $campusesList,
            'challenges' => $challengesList,
            'contest'    => $contestEdit,
            'challengesInContest' => $challengesInContestEdit,
        ]);
    }

    /**
     * @param string $contestId
     */
    public function setContestActive(string $contestId)
    {
        $contestManager = new ContestManager();
        $contestManager->setContestActive($contestId);
        Dispatch::toUrl('/admin/managecontest');
    }

    /**
     *
     */
    public function displayContest()
    {
        $json           = file_get_contents('php://input');
        $object         = json_decode($json);
        $contestId      = $object->id;
        $isVisible      = $object->visible;
        $contestManager = new ContestManager();

        if ($isVisible === '0') {
            $contestManager->displayOnForContest($contestId);
        } elseif ($isVisible === '1') {
            $contestManager->displayOffForContest($contestId);
        }
    }

    // USERS

    /**
     * Take an integer as input to manage pagination
     * @param int $page Default 1
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function manageUsers(int $page = 1)
    {
        $userManager = new UserManager();
        $users       = $userManager->selectAllOrderBy('lastname', 'ASC', $page, $_SESSION['user_id']);
        return $this->twig->render('Admin/users.html.twig', [
            'users'        => $users,
            'number_pages' => UserPaginator::numberOfPages($userManager),
            'is_page'      => $page
        ]);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function setUserAdmin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json        = file_get_contents('php://input');
            $data        = json_decode($json, true);
            $userManager = new UserManager();
            $status      = ($data['status']) ? UserManager::ADMIN : UserManager::NOT_ADMIN;
            $isAdmin     = $_SESSION['is_admin'] === true;

            if ($isAdmin) {
                if ($data['status']) {
                    $text = $data['username'] . ' is now admin';
                    $userManager->userSetAdmin($status, $data['user_id']);
                } else {
                    $text = $data['username'] . ' is not admin anymore';
                    $userManager->userSetAdmin($status, $data['user_id']);
                }
            } else {
                $text = 'You haven\'t got the good rights to do this';
            }
            return $this->twig->render('/Ajaxviews/toast_admin_user.html.twig', [
                'data' => $text,
            ]);
        }
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function setUserActive()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json        = file_get_contents('php://input');
            $data        = json_decode($json, true);
            $userManager = new UserManager();
            $status      = ($data['status']) ? UserManager::ACTIVE : UserManager::NOT_ACTIVE;
            $isAdmin     = $_SESSION['is_admin'] === true;

            if ($isAdmin) {
                if ($data['status']) {
                    $text = $data['username'] . ' is now active';
                    $userManager->userSetActive($status, $data['user_id']);
                } else {
                    $text = $data['username'] . ' is not active anymore';
                    $userManager->userSetActive($status, $data['user_id']);
                }
            } else {
                $text = 'You haven\'t got the good rights to do this';
            }

            return $this->twig->render('/Ajaxviews/toast_admin_user.html.twig', [
                'data' => $text,
            ]);
        }
    }

    // CAMPUS

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function addCampus()
    {
        $campusManager   = new CampusManager();
        $errors          = [];
        $campus          = null;
        $campusExist     = false;
        $campuses        = $campusManager->getAllCampusOrderBy('country', 'ASC', 'city', 'ASC');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campus = new CampusFormControl($_POST);
            $errors = $campus->getErrors();
            if (count($errors) === 0) {
                if (!$campusManager->campusExists($campus)) {
                    $campusManager->insertCampus($campus);
                    Dispatch::toUrl('/admin/addCampus');
                } else {
                    $campusExist = true;
                }
            }
        }
        $result=[
            'errors'=>$errors,
            'campus'=>$campus,
            'campuses'=>$campuses,
            'campus_exist'=>$campusExist,
        ];
        return $this->twig->render('Admin/campus.html.twig', $result);
    }

    // LANGUAGES

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function addSpecialty()
    {
        $specialtyManager = new SpecialtyManager();
        $errors           = [];
        $specialty        = null;
        $specialties      = $specialtyManager->selectAll();
        $specialtyExist   = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $specialty = new SpecialtyFormControl($_POST);
            $errors    = $specialty->getErrors();
            if (count($errors) === 0) {
                if (!$specialtyManager->specialtyExists($specialty)) {
                    $specialtyManager->insertSpecialty($specialty);
                    Dispatch::toUrl('/admin/addSpecialty');
                } else {
                    $specialtyExist = true;
                }
            }
        }
        $result=[
            'errors'      => $errors,
            'specialty'   => $specialty,
            'specialties' => $specialties,
            'specialty_exist' => $specialtyExist,
        ];
        return $this->twig->render('Admin/specialty.html.twig', $result);
    }

    // TYPES

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function addType()
    {
        $typeManager = new TypeManager();
        $errors      = [];
        $type        = null;
        $types       = $typeManager->selectAll();
        $typeExist  = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type   = new TypeFormControl($_POST);
            $errors = $type->getErrors();
            if (count($errors) === 0) {
                if (!$typeManager->typeExists($type)) {
                    $typeManager->insertType($type);
                    $types = $typeManager->selectAll();
                } else {
                    $typeExist = true;
                }
            }
        }
        $result=[
            'errors'     => $errors,
            'type'       => $type,
            'types'      => $types,
            'type_exist' => $typeExist,
        ];
        return $this->twig->render('Admin/type.html.twig', $result);
    }
}
