<?php


namespace App\Service;

use App\Controller\AbstractController;
use App\Model\UserManager;

class UserService extends AbstractController
{
    public static function openConnection(int $idUser): void
    {
        $user = new UserManager();

        $userConnected = $user->selectOneById($idUser);

        $userConnected['user_id']      = (int)$userConnected['id'];
        unset($userConnected['id']);
        $userConnected['is_admin']     = (bool)$userConnected['is_admin'];
        $userConnected['is_active']    = (bool)$userConnected['is_active'];
        $userConnected['specialty_id'] = (int)$userConnected['specialty_id'];
        $userConnected['campus_id']    = (int)$userConnected['campus_id'];

        foreach ($userConnected as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public static function mailAlreadyExists(string $eMail): bool
    {
        $userManager = new UserManager();
        if (!$userManager->selectOneByEmail($eMail)) {
            $return = false;
        } else {
            $return = true;
        }
        return $return;
    }
}
