<?php

namespace Osana\Challenge\Services\GitHub;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UsersRepository;
use Tightenco\Collect\Support\Collection;

class GitHubUsersRepository implements UsersRepository
{
    public function findByLogin(Login $name, int $limit = 0): Collection
    {
        if(!$this->_checkRateLimit()){
            return collect(array());
        }
        $url = "https://api.github.com/search/users?q=".$name->getValue()."&per_page=".$limit;
        
        $usersData = $this->_connectGithubApi($url);
        
        $users = array();
        foreach($usersData["items"] as $userData){
            $urlUser = $userData["url"];
            $retUser = $this->_connectGithubApi($urlUser);
            $name = $retUser["name"] == null ? '':$retUser["name"];
            $company = $retUser["company"] == null ? '':$retUser["company"];
            $location = $retUser["location"] == null ? '':$retUser["location"];
            $profile = new Profile(new Name($name), new Company($company), new Location($location));
            $user = new User(new Id($userData["id"]), new Login($userData["login"]), new Type('github'), $profile);
            array_push($users, $user);
        }
        return collect($users);
    }

    public function getByLogin(Login $name, int $limit = 0): User
    {
        // TODO: implement me
        $user = new User(new Id(0), new Login(''), new Type('local'), new Profile(new Name(''), new Company(''), new Location('')));
        if(!$this->_checkRateLimit()){
            return $user;
        }
        $url = "https://api.github.com/users/".$name->getValue();
        
        $userData = $this->_connectGithubApi($url);
        if(isset($userData["message"])){
            return $user;
        }
        $name = $userData["name"] == null ? '':$userData["name"];
        $company = $userData["company"] == null ? '':$userData["company"];
        $location = $userData["location"] == null ? '':$userData["location"];
        $profile = new Profile(new Name($name), new Company($company), new Location($location));
        $user = new User(new Id($userData["id"]), new Login($userData["login"]), new Type('github'), $profile);
        return $user;
    }

    public function add(User $user): void
    {
        throw new OperationNotAllowedException();
    }
    
    private function _checkRateLimit(){
        $url = "https://api.github.com/rate_limit";
        $retArr = $this->_connectGithubApi($url);
        return $retArr["rate"]["remaining"];
    }
    
    private function _connectGithubApi($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Accept: application/vnd.github.v3+json', 'User-Agent: Osana-Challenge-App'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $ret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return json_decode($ret, true);
    }
}
