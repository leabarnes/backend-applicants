<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StoreUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // TODO: implement me
        $data = $request->getParsedBody();
        if(!isset($data["login"]) || !$data["login"] || !isset($data["profile"])){
            $response->getBody()->write(json_encode(array("error" => true, "message" => "No se ingresaron los datos correctamente.")));

            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(404, 'Error');
        }
        $profileData = $data["profile"];
        $name = $profileData["name"] == null ? '':$profileData["name"];
        $company = $profileData["company"] == null ? '':$profileData["company"];
        $location = $profileData["location"] == null ? '':$profileData["location"];
        $profile = new Profile(new Name($name), new Company($company), new Location($location));
        $user = new User(new Id(''), new Login($data["login"]), new Type('local'), $profile);
        $this->localUsersRepository->add($user);
        $newUser = $this->localUsersRepository->getByLogin(new Login($data["login"]), 1);
        $newUser = [
                'id' => $newUser->getId()->getValue(),
                'login' => $newUser->getLogin()->getValue(),
                'type' => $newUser->getType()->getValue(),
                'profile' => [
                    'name' => $newUser->getProfile()->getName()->getValue(),
                    'company' => $newUser->getProfile()->getCompany()->getValue(),
                    'location' => $newUser->getProfile()->getLocation()->getValue(),
                ]
            ];
        $response->getBody()->write(json_encode($newUser));

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }
}
