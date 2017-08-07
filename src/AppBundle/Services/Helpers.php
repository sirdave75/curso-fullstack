<?php
/**
 * Created by PhpStorm.
 * User: binll
 * Date: 04/08/2017
 * Time: 10:52
 */

namespace AppBundle\Services;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;


class Helpers
{

    public $jwt_auth;
    public function __construct(JwtAuth $jwtAuth)
    {
        $this->jwt_auth = $jwtAuth;
    }

    public function authCheck($hash, $getIdentity = false){
        $jwt_auth = $this->jwt_auth;
        $auth = false;
        if($hash != null){
            if($getIdentity == false){
                $check_token = $jwt_auth->checkToken($hash);
                if($check_token){
                    $auth = true;
                }
            }
            else{
                $check_token = $jwt_auth->checkToken($hash, true);
                if(is_object($check_token)){
                    $auth = $check_token;
                }
            }
        }

        return $auth;

    }

    //Método que devuelve un json
    public function getJson($data){
        $normalizers = array(new GetSetMethodNormalizer());
        $encoders = array("json" => new JsonEncoder());

        $serializer  = new Serializer($normalizers,$encoders);
        $json = $serializer->serialize($data,'json');

        $response = new Response();
        $response->setContent($json);
        $response->headers->set("Content-Type", "application/json");
        return $response;

    }



    public function hola(){
        return "Hola desde el servicio";
    }
}