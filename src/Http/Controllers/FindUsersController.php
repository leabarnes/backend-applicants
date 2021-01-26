<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FindUsersController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    /** @var GitHubUsersRepository */
    private $gitHubUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository, GitHubUsersRepository $gitHubUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
        $this->gitHubUsersRepository = $gitHubUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $limit = $request->getQueryParams()['limit'] ?? 0;

        $login = new Login($query);

        // FIXME: Se debe tener cuidado en la implementación
        // para que siga las notas del documento de requisitos
        $githubUsers = $this->gitHubUsersRepository->findByLogin($login, $limit);
        $localUsers = $this->localUsersRepository->findByLogin($login, $limit);
        
        $countGit = count($githubUsers);
        $countLocal = count($localUsers);
        
        if($countGit >= $limit/2){
            if($countLocal >= $limit/2){
                $countLocal = $countGit = $limit/2;
            } else {
                $countGit = $limit - $countLocal;
            }
        } else {
            if($countLocal > $limit/2){
                $countLocal = $limit - $countGit;
            }
        }
        $localUsers = $this->cutCollection($localUsers, $countLocal);
        $githubUsers = $this->cutCollection($githubUsers, $countGit);
        $users = $localUsers->merge($githubUsers)->map(function (User $user) {
            return [
                'id' => $user->getId()->getValue(),
                'login' => $user->getLogin()->getValue(),
                'type' => $user->getType()->getValue(),
                'profile' => [
                    'name' => $user->getProfile()->getName()->getValue(),
                    'company' => $user->getProfile()->getCompany()->getValue(),
                    'location' => $user->getProfile()->getLocation()->getValue(),
                ]
            ];
        });

        $response->getBody()->write($users->toJson());

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }
    
    private function cutCollection($collection, $limit){
        $arrAux = array();
        for($i = 0; $i < $limit; $i++){
            if(isset($collection[$i])){
                array_push ($arrAux, $collection[$i]);
            } else {
                break;
            }
        }
        return collect($arrAux);
    }
}
