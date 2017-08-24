<?php
/**
 * Created by PhpStorm.
 * User: binll
 * Date: 04/08/2017
 * Time: 12:25
 */

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Symfony\Component\Cache\Adapter\DoctrineAdapter;

class JwtAuth
{
    public $manager;
    public $key ;

    public  function  __construct(EntityManager $manager){

        $this->manager = $manager;
        $this->key = "clave-secreta";
    }

    public function singup($email,$password,$getHash = NULL){
        $key = $this->key;

        $user = $this->manager->getRepository('BackendBundle:User')->findOneBy(
            [
                "email" => $email,
                "password" => $password
            ]
        );

        $singup = false;
        if(is_object($user)){
            $singup = true;
        }

        if($singup){
            $token = [
                "sub" => $user->getId(),
                "email" => $user->getEmail(),
                "password" => $user->getPassword(),
                "name" => $user->getName(),
                "surname" => $user->getSurname(),
                "image" => $user->getImage(),
                "iat" => time(),
                "exp" => time() + (7 * 24 * 60 * 60)
            ];
            $jwt = JWT::encode($token,$key,'HS256');
            $decoded = JWT::decode($jwt,$key, ['HS256']);

            if($getHash != null) return $jwt;
            else{
                return $decoded;
            }


        }
        else{
            return ["status" => "error","data" => "login failed !!"];
        }
    }



    public function checkToken($jwt,$getIdentity = false){
        $key = $this->key;
        $auth = false;

        try{
            $decoded = JWT::decode($jwt,$key, ['HS256']);

        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }

        if(isset($decoded->sub)){
            $auth = true;
        }
        else{
            $auth = false;
        }

        if($getIdentity == true){
            return $decoded;
        }
        else{
            return $auth;
        }
    }


}