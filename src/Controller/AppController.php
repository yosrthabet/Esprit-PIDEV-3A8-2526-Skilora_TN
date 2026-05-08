<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Base controller for all Skilora controllers.
 * Provides getAppUser() which returns App\Entity\User (typed) instead of UserInterface.
 */
abstract class AppController extends AbstractController
{
    /**
     * Returns the currently authenticated user as App\Entity\User.
     * Throws AccessDenied if not logged in or user is not the expected type.
     */
    protected function getAppUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }
}
