<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ShowUserController {

    /** @var LocalUsersRepository */
    private $localUsersRepository;

    /** @var GitHubUsersRepository */
    private $gitHubUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository, GitHubUsersRepository $gitHubUsersRepository) {
        $this->localUsersRepository = $localUsersRepository;
        $this->gitHubUsersRepository = $gitHubUsersRepository;
    }

    public function __invoke(Request $request, Response $response, array $params): Response {
        if ($params['type'] != 'github' && $params['type'] != 'local') {
            $response->getBody()->write(json_encode(array("error" => true, "message" => "El tipo ingresado no esta definido")));

            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(404, 'Error');
        }
        $type = new Type($params['type']);
        $login = new Login($params['login']);

        // TODO: implement me
        if ($type->getValue() == "github") {
            $user = $this->gitHubUsersRepository->getByLogin($login, 1);
        } else {
            $user = $this->localUsersRepository->getByLogin($login, 1);
        }
        $user = [
                'id' => $user->getId()->getValue(),
                'login' => $user->getLogin()->getValue(),
                'type' => $user->getType()->getValue(),
                'profile' => [
                    'name' => $user->getProfile()->getName()->getValue(),
                    'company' => $user->getProfile()->getCompany()->getValue(),
                    'location' => $user->getProfile()->getLocation()->getValue(),
                ]
            ];
        $response->getBody()->write(json_encode($user));

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }

}
