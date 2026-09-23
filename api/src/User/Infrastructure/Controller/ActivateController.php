<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ActivateUserByToken\ActivateUserByTokenCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[OA\Tag(name: 'Auth')]
final class ActivateController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Environment $twig,
    ) {}

    #[Route('/api/activate', name: 'api_activate', methods: ['GET'])]
    #[OA\Parameter(
        name: 'token',
        description: 'Account activation token sent by email',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Account activated successfully (renders an HTML page)',
        content: new OA\MediaType(mediaType: 'text/html'),
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing or invalid activation token (renders an HTML error page)',
        content: new OA\MediaType(mediaType: 'text/html'),
    )]
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
