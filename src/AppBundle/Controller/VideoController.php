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
            return $helpers->getJson($data);
        }
    public function editAction(Request $request,$id = null){
        $helpers = $this->get(Helpers::class);
        $hash = $request->get("authorization",null);
        $authCheck = $helpers->authCheck($hash);
        if($authCheck){
            $identity = $helpers->authCheck($hash,true);
            $json = $request->get("json",true);
            if($json != null) {
                $params = json_decode($json);
                $video_id = $id;
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
                    $video = $em->getRepository("BackendBundle:Video")->findOneBy([
                       "id" => $video_id
                    ]);
                    if(isset($identity->sub) && $identity->sub == $video->getUser()->getId()) {

                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setStatus($status);
                        $video->setUpdatedAt($createdAt);

                        $em->persist($video);
                        $em->flush();

                        $data = [
                            "status" => "success",
                            "code" => 200,
                            "msg" => "Vídeo actualizado correctamente"
                        ];
                    }
                    else{
                        $data = [
                            "status" => "error",
                            "code" => 400,
                            "msg" => "Vídeo no actualizado."
                        ];
                    }
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
        return $helpers->getJson($data);
    }

    public function uploadAction(Request $request,$id){
        $helpers = $this->get(Helpers::class);
        $hash = $request->get("authorization",null);
        $authCheck = $helpers->authCheck($hash);
        //var_dump($hash);
        if($authCheck) {
            $identity = $helpers->authCheck($hash, true);

            $video_id = $id;

            $em = $this->getDoctrine()->getManager();
            $video = $em ->getRepository("BackendBundle:Video")->findOneBy([
               "id" => $video_id
            ]);

            if($video_id != null && isset($identity->sub) && $identity->sub == $video->getUser()->getId()){
                $file = $request->files->get('image', null);
                $file_video = $request->files->get('video', null);
                if($file != null && !empty($file)){
                    $ext = $file->guessExtension();
                    if($ext == 'jpeg' || $ext == 'jpg' || $ext == 'png' || $ext == 'gif') {

                        $file_name = time().'.'.$ext;
                        $path_of_file = "uploads/video_images/video_".$video_id;
                        $file->move($path_of_file, $file_name);

                        $video->setImage($file_name);
                        $em->persist($video);
                        $em->flush();
                        $data = [
                            "status" => "success",
                            "code" => 200,
                            "msg" => "Imagen  del video subida correctamente"
                        ] ;
                    }
                    else{
                        $data = [
                            "status" => "error",
                            "code" => 400,
                            "msg" => "Extensión de la imagen no válida"
                        ] ;
                    }
                }
                else{
                    if($file_video != null && !empty($file_video)){
                        $ext = $file_video->guessExtension();
                        if($ext == 'mp4' || $ext == 'avi') {

                            $file_name = time().'.'.$ext;
                            $path_of_file = "uploads/video_files/video_".$video_id;
                            $file_video->move($path_of_file, $file_name);

                            $video->setVideoPath($file_name);
                            $em->persist($video);
                            $em->flush();

                            $data = [
                                "status" => "success",
                                "code" => 200,
                                "msg" => "Vídeo subido correctamente"
                            ] ;
                        }
                        else{

                            $data = [
                                "status" => "error",
                                "code" => 400,
                                "msg" => "Extensión del vídeo no válida"
                            ] ;
                        }
                    }
                }
            }
            else{
                $data = [
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Video updated error, you not owner"
                ] ;

            }

        }
        else{
            $data = [
                "status" => "error",
                "code" => 400,
                "msg" => "Usuario no autorizado"
            ] ;
        }
        return $helpers->getJson($data);
    }

    public function videosAction(Request $request){

        $helpers = $this->get(Helpers::class);

        $em = $this->getDoctrine()->getManager();

        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";

        $query = $em->createQuery($dql);
        //recoge el número que trae el parámetro page
        $page = $request->query->getInt('page',1);

        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;

        $pagination = $paginator->paginate($query,$page,$items_per_page);
        $total_items_count = $pagination->getTotalItemCount();

        $data = [
            "status" => "success",
            "total_items_count" => $total_items_count,
            "page_actual" => $page,
            "item_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_count/$items_per_page),
            "data" => $pagination
        ];
        return $helpers->getJson($data);
    }

    public function lastsVideosAction(Request $request){
        $helpers = $this->get(Helpers::class);
        $em = $this->getDoctrine()->getManager();

        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.createdAt DESC";
        $query = $em->createQuery($dql)->setMaxResults(5);
        $videos = $query->getResult();

        $data = [
            "status" => "success",
            "data" => $videos
        ];

        return $helpers->getJson($data);


    }

    public function videoAction(Request $request, $id = null){
        $helpers = $this->get(Helpers::class);
        $em = $this->getDoctrine()->getManager();

        $video = $em ->getRepository("BackendBundle:Video")->findOneBy([
            "id" => $id
        ]);

        if($video){
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
                "msg" => "Video no existe"
            ];
        }



        return $helpers->getJson($data);

    }

    public function searchAction(Request $request, $search = null){
        $helpers = $this->get(Helpers::class);
        $em = $this->getDoctrine()->getManager();

        if($search != null){
            $dql = "SELECT v FROM BackendBundle:Video v 
                    WHERE v.title like '%$search%' OR 
                    v.description like '%$search%' ORDER BY v.id DESC";
        }
        else{
            $dql = "SELECT v FROM BackendBundle:Video v Order BY v.id DESC";
        }

        $query = $em->createQuery($dql);

        //recoge el número que trae el parámetro page
        $page = $request->query->getInt('page',1);

        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;

        $pagination = $paginator->paginate($query,$page,$items_per_page);
        $total_items_count = $pagination->getTotalItemCount();

        $data = [
            "status" => "success",
            "total_items_count" => $total_items_count,
            "page_actual" => $page,
            "item_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_count/$items_per_page),
            "data" => $pagination
        ];
        return $helpers->getJson($data);

    }
}