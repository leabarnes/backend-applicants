<?php

namespace Osana\Challenge\Services\Local;

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

class LocalUsersRepository implements UsersRepository {

    public function findByLogin(Login $login, int $limit = 0): Collection {
        // TODO: implement me
        $resultCount = 0;
        $users = array();
        $file = fopen("../data/users.csv", "r");
        $fileProf = fopen("../data/profiles.csv", "r");
        $datos = fgetcsv($file, 1000);
        while (($datos = fgetcsv($file, 1000)) !== false && $resultCount < $limit) {
            if (preg_match("/" . $login->getValue() . ".*/", $datos[1])) {
                while (($datosProf = fgetcsv($fileProf, 1000)) !== false) {
                    if ($datos[0] == $datosProf[0]) {
                        $name = $datosProf[1] == null ? '' : $datosProf[3];
                        $company = $datosProf[1] == null ? '' : $datosProf[1];
                        $location = $datosProf[1] == null ? '' : $datosProf[2];
                        $profile = new Profile(new Name($name), new Company($company), new Location($location));
                        break;
                    }
                }
                $user = new User(new Id($datos[0]), new Login($datos[1]), new Type('local'), $profile);
                $resultCount++;
                array_push($users, $user);
            }
        }
        fclose($fileProf);
        fclose($file);
        return collect($users);
    }

    public function getByLogin(Login $login, int $limit = 0): User {
        // TODO: implement me
        $file = fopen("../data/users.csv", "r");
        $fileProf = fopen("../data/profiles.csv", "r");
        $datos = fgetcsv($file, 1000);
        $user = new User(new Id(0), new Login(''), new Type('local'), new Profile(new Name(''), new Company(''), new Location('')));
        while (($datos = fgetcsv($file, 1000)) !== false) {
            if ($login->getValue() == $datos[1]) {
                while (($datosProf = fgetcsv($fileProf, 1000)) !== false) {
                    if ($datos[0] == $datosProf[0]) {
                        $name = $datosProf[1] == null ? '' : $datosProf[3];
                        $company = $datosProf[1] == null ? '' : $datosProf[1];
                        $location = $datosProf[1] == null ? '' : $datosProf[2];
                        $profile = new Profile(new Name($name), new Company($company), new Location($location));
                        break;
                    }
                }
                $user = new User(new Id($datos[0]), new Login($datos[1]), new Type('local'), $profile);
                break;
            }
        }
        fclose($fileProf);
        fclose($file);
        return $user;
    }

    public function add(User $user): void {
        // TODO: implement me
        $fileR = fopen("../data/users.csv", "r");
        $lastID = "";
        while (($datos = fgetcsv($fileR, 1000)) !== false) {
            $lastID = str_replace("CSV", "", $datos[0]);
        }
        fclose($fileR);
        $newId = ((int) $lastID) + 1;
        $uProfile = $user->getProfile();
        $fileW = fopen("../data/users.csv", "a");
        $userArr = array(
            "id" => "CSV" . $newId,
            "login" => $user->getLogin()->getValue(),
            "type" => 'local'
        );
        fputcsv($fileW, $userArr);
        fclose($fileW);

        $fileProf = fopen("../data/profiles.csv", "a");
        $profArr = array(
            "id" => "CSV" . $newId,
            "company" => $uProfile->getCompany()->getValue(),
            "location" => $uProfile->getLocation()->getValue(),
            "name" => $uProfile->getName()->getValue()
        );
        fputcsv($fileProf, $profArr);
        fclose($fileProf);
    }

}
