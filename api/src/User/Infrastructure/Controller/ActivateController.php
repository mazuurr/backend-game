<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ActivateUserByToken\ActivateUserByTokenCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class ActivateController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Environment $twig,
    ) {}

    #[Route('/api/activate', name: 'api_activate', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $token = $request->query->get('token');

        if ($token === null || $token === '') {
            return new Response(
                $this->twig->render('activation/error.html.twig', [
                    'error' => 'Brak tokenu aktywacyjnego.',
                ]),
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $this->commandBus->dispatch(new ActivateUserByTokenCommand($token));
        } catch (\DomainException $e) {
            return new Response(
                $this->twig->render('activation/error.html.twig', [
                    'error' => $e->getMessage(),
                ]),
                Response::HTTP_BAD_REQUEST,
            );
        }

        return new Response(
            $this->twig->render('activation/success.html.twig'),
            Response::HTTP_OK,
        );
    }
}
