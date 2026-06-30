<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    private const SUPPORTED = ['fr', 'es'];

    #[Route('/langue/{locale}', name: 'app_set_locale')]
    public function setLocale(string $locale, Request $request): Response
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            $request->getSession()->set('_locale', $locale);
        }

        $referer = $request->headers->get('referer', $this->generateUrl('app_dashboard'));

        return $this->redirect($referer);
    }
}
