<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale_switch', methods: ['GET'], requirements: ['locale' => 'en|fr|ar'])]
    public function switchLocale(string $locale, Request $request): Response
    {
        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('app_home'));
    }
}
