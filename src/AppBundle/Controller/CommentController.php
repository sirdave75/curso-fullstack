<?php
/**
 * Created by PhpStorm.
 * User: binll
 * Date: 21/08/2017
 * Time: 9:42
 */

namespace AppBundle\Controller;


use AppBundle\Services\Helpers;
use BackendBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;


use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;

class CommentController extends Controller
{

    public function newAction(Request $request){
        $helpers = $this->get(Helpers::class);

        $hash = $request->get("authorization", null);

        $authCheck = $helpers->authCheck($hash);

        if($authCheck){
            $identity = $helpers->authCheck($hash, true);

            $json = $request->get("json", null);

            if($json != null){
                $params = json_decode($json);
                $createdAt = new \DateTime('now');
                $user_id = (isset($identity->sub)) ? $identity->sub : null;
                $video_id = (isset($params->video_id)) ? $params->video_id : null;
                $body = (isset($params->body)) ? $params->body : null;


                if($user_id != null && $video_id != null){
                    $em = $this->getDoctrine()->getManager();
                    $user = $em -> getRepository("BackendBundle:User")->findOneBy([
                       "id" => $user_id
                    ]);
                    $video = $em -> getRepository("BackendBundle:Video")->findOneBy([
                        "id" => $video_id
                    ]);

                    $comment = new Comment();
                    $comment->setUser($user);
                    $comment->setVideo($video);
                    $comment->setBody($body);
                    $comment->setCreatedAt($createdAt);

                    $em->persist($comment);
                    $em->flush();

                    $data = [
                        "status" => "success",
                        "code" => 200,
                        "msg" => "Comment  created"
                    ];
                }
                else{
                    $data = [
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Comment not created"
                    ];
                }
            }
            else{

                $data = [
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Params no válido"
                ];
            }
        }
        else{
            $data = [
              "status" => "error",
              "code" => 400,
              "msg" => "Usuario no válido"
            ];
        }
        return $helpers->getJson($data);
    }

    public  function  deleteAction(Request$request, $id = null){
        $helpers = $this->get(Helpers::class);

        $hash = $request->get("authorization", null);

        $authCheck = $helpers->authCheck($hash);

        if($authCheck){
            $identity = $helpers->authCheck($hash, true);

            $user_id = ($identity->sub != null) ? $identity->sub : null;

            $em = $this->getDoctrine()->getManager();
            $comment = $em->getRepository("BackendBundle:Comment")->findOneBy([
               "id" => $id
            ]);
            if(is_object($comment) && $user_id != null){
                if(isset($identity->sub) &&
                    ($identity->sub == $comment->getUser()->getId() ||
                     $identity->sub == $comment->getVideo()->getUser()->getId()
                    )){
                    $em -> remove($comment);
                    $em->flush();

                    $data = [
                        "status" => "success",
                        "code" => 200,
                        "msg" => "Comment delete success"
                    ];
                }
                else{

                    $data = [
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Comment not delete"
                    ];
                }

            }
            else{

                $data = [
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Comment not delete"
                ];
            }
         }
        else{
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Authentication no válido"
            ];
        }
        return $helpers->getJson($data);
    }


    public function listAction(Request $request, $id = null){
        $helpers = $this->get(Helpers::class);
        $em = $this->getDoctrine()->getManager();

        $video = $em->getRepository("BackendBundle:Video")->findOneBy([
           "id" => $id
        ]);
        $comments = $em->getRepository("BackendBundle:Comment")->findBy([
           "video" => $video
        ], ["id" => "DESC"]);

        if(count($comments) >= 1){
            $data = [
                "status" => "success",
                "code" => 200,
                "data" => $comments
            ];
        }
        else{
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Dont exists comments in this video!!"
            ];
        }

        return $helpers->getJson($data);

    }


}