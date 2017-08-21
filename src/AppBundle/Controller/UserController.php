<?php
/**
 * Created by PhpStorm.
 * User: binll
 * Date: 07/08/2017
 * Time: 11:20
 */

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use BackendBundle\BackendBundle;
use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{
        public function newAction(Request $request){

           $helpers = $this->get(Helpers::class);
           $json = $request->get("json",null);
           $params = json_decode($json);
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Usuario no se ha creado"
            ];
           if($json != null) {

               $createdAt = new \DateTime("now");
               $image = null;
               $role = "user";

               $email = (isset($params->email)) ? $params->email : null;
               $name = (isset($params->name) && ctype_alpha($params->name)) ? $params->name : null;
               $surname = (isset($params->surname) && ctype_alpha($params->surname)) ? $params->surname : null;
               $password = (isset($params->password)) ? $params->password : null;

               $emailConstraint = new Assert\Email();
               $emailConstraint->message = "This email is not valid !!";
               $validate_email = $this->get("validator")->validate($email, $emailConstraint);

               if($email != null && count($validate_email) == 0 &&
                   $password != null && $name != null && $surname != null){
                   $user = new User();

                   $user -> setCreatedAt($createdAt);
                   $user -> setImage($image);
                   $user -> setRole($role);
                   $user -> setEmail($email);
                   $user -> setName($name);
                   $user -> setSurname($surname);

                   //cifrar la contraseña

                   $pwd = hash('sha256',$password);
                   $user -> setPassword($pwd);

                   $em = $this->getDoctrine()->getManager();
                   $isset_user = $em ->getRepository("BackendBundle:User")->findBy([
                       "email" => $email
                   ]);

                   if(count($isset_user)== 0){
                       $em->persist($user);
                       $em->flush();
                       $data = [
                           "status" => "success",
                           "code" => 200,
                           "msg" => "Nuevo usuario creado !!"
                       ];
                   }
                   else{
                       $data = [
                           "status" => "error",
                           "msg" => "Usuario duplicado !!"
                       ];
                   }
               }
           }
           return $helpers->getJson($data);
        }

    public function editAction(Request $request){

        $helpers = $this->get(Helpers::class);
        $hash = $request->get("authorization",null);
        $authCheck = $helpers->authCheck($hash);
        if($authCheck){
            $identity = $helpers->authCheck($hash,true);
            $em = $this->getDoctrine()->getManager();
            $user = $em -> getRepository("BackendBundle:User")->findOneBy([
               "id" => $identity->sub
            ]);

            $json = $request->get("json",null);
            $params = json_decode($json);
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Usuario no se ha actualizado: ".$identity->sub
            ];

            if($json != null) {

                $createdAt = new \DateTime("now");
                $image = null;
                $role = "user";

                $email = (isset($params->email)) ? $params->email : null;
                $name = (isset($params->name) && ctype_alpha($params->name)) ? $params->name : null;
                $surname = (isset($params->surname) && ctype_alpha($params->surname)) ? $params->surname : null;
                $password = (isset($params->password)) ? $params->password : null;

                $emailConstraint = new Assert\Email();
                $emailConstraint->message = "This email is not valid !!";
                $validate_email = $this->get("validator")->validate($email, $emailConstraint);

                if($email != null && count($validate_email) == 0 &&
                    $name != null && $surname != null){

                    $user -> setCreatedAt($createdAt);
                    $user -> setImage($image);
                    $user -> setRole($role);
                    $user -> setEmail($email);
                    $user -> setName($name);
                    $user -> setSurname($surname);

                    if($password != null) {
                        //cifrar la contraseña
                        $pwd = hash('sha256', $password);
                        $user->setPassword($pwd);
                    }
                    $em = $this->getDoctrine()->getManager();
                    $isset_user = $em ->getRepository("BackendBundle:User")->findBy([
                        "email" => $email
                    ]);

                    if(count($isset_user)== 0 || $identity->email == $email){
                        $em->persist($user);
                        $em->flush();
                        $data = [
                            "status" => "success",
                            "code" => 200,
                            "msg" => "Usuario actualizado !!"
                        ];
                    }
                    else{
                        $data = [
                            "status" => "error",
                            "msg" => "Usuario duplicado !!"
                        ];
                    }
                }
            }
        }
        else{
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Usuario no autorizado"
            ];
        }


        return $helpers->getJson($data);
    }

    public function uploadImageAction(Request $request){
        $helpers = $this->get(Helpers::class);

        $hash = $request->get("authorization",null);
        $authCheck = $helpers->authCheck($hash);

        if($authCheck){
            $identity = $helpers->authCheck($hash,true);
            $em = $this->getDoctrine()->getManager();
            $user = $em -> getRepository("BackendBundle:User")->findOneBy([
               "id" => $identity->sub
            ]);

            //upload file
            $file = $request->files->get("image");
           // var_dump($file);
            if(!empty($file) && $file != null){
                $ext = $file->guessExtension();
                if($ext == "jpeg" || $ext == "jpg" || $ext == "png" || $ext == "gif") {
                    $file_name = time().".".$ext;
                    $file->move("uploads/users", $file_name);

                    $user->setImage($file_name);
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        "status" => "success",
                        "code" => 200,
                        "msg" => "Imagen subida correctamente"
                    ];
                }
                else{
                    $data = [
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Extensión no válida"
                    ];
                }
            }
            else{
                $data = [
                    "status" => "error",
                    "code"  => 400,
                    "msg"   => "La imagen no se ha subido: "
                ];
            }
        }
        else{
            $data = [
                "status" => "error",
                "code"  => 400,
                "msg"   => "Authorization not valid"
            ];
        }

        return $helpers->getJson($data);
    }



    public function channelAction(Request $request, $id = null){

        $helpers = $this->get(Helpers::class);

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository("BackendBundle:User")->findOneBy([
           "id" => $id
        ]);

        //pasamos $id en lugar de un objeto porque busca en la primary key
        $dql = "SELECT v FROM BackendBundle:Video v  WHERE v.user = $id ORDER BY v.id DESC";

        $query = $em->createQuery($dql);
        //recoge el número que trae el parámetro page
        $page = $request->query->getInt('page',1);

        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;

        $pagination = $paginator->paginate($query,$page,$items_per_page);
        $total_items_count = $pagination->getTotalItemCount();

        if(count($user)==1) {
            $data = [
                "status" => "success",
                "total_items_count" => $total_items_count,
                "page_actual" => $page,
                "item_per_page" => $items_per_page,
                "total_pages" => ceil($total_items_count / $items_per_page),

            ];
            $data["data"]["videos"] = $pagination;
            $data["data"]["user"] = $user;
        }
        else{

            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Usuario no existe"

            ];
        }
        return $helpers->getJson($data);
    }
}