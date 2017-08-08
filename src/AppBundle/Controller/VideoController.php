<?php
/**
 * Created by PhpStorm.
 * User: binll
 * Date: 08/08/2017
 * Time: 9:39
 */

namespace AppBundle\Controller;


use AppBundle\Services\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;


use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;

class VideoController extends Controller
{
        public function newAction(Request $request){
            $helpers = $this->get(Helpers::class);
            $hash = $request->get("authorization",null);
            $authCheck = $helpers->authCheck($hash);
            if($authCheck){
                $identity = $helpers->authCheck($hash,true);
                $json = $request->get("json",true);
                if($json != null) {
                    $params = json_decode($json);

                    $createdAt = new \DateTime('now');
                    $updatedAt = new \DateTime('now');
                    $imagen = null;
                    $video_path = null;

                    $user_id = ($identity->sub != null) ? $identity->sub : null;
                    $title = (isset($params->title)) ? $params->title : null;
                    $description = (isset($params->description)) ? $params->description : null;
                    $status = (isset($params->status)) ? $params->status : null;

                    if($user_id != null && $title != null){
                        $em = $this->getDoctrine()->getManager();
                        $user = $em->getRepository("BackendBundle:User")->findOneBy([
                           "id" => $user_id
                        ]);
                        $video = new Video();
                        $video->setUser($user);
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setStatus($status);
                        $video->setCreatedAt($createdAt);
                        $video->setUpdatedAt($createdAt);

                        $em->persist($video);
                        $em->flush();

                        $video = $em->getRepository("BackendBundle:Video")->findOneBy([
                           "user"       => $user,
                           "title"      => $title,
                           "status"     => $status,
                           "createdAt"  => $createdAt
                        ]);
                        $data = [
                            "status" => "success",
                            "code" => 200,
                            "data" => $video
                        ];
                    }
                    else{
                        $data = [
                            "status" => "error",
                            "code" => 400,
                            "msg" => "video not created"
                        ];
                    }

                }
                else{
                    $data = [
                        "status" => "error",
                        "code" => 400,
                        "msg" => "video not created, params failed"
                    ];
                }
            }
            else{
                $data = [
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Usuario no autorizado"
                ];
            }
        }
}