<?php



/*!

 * ifsoft.co.uk v1.1

 *

 * http://ifsoft.com.ua, http://ifsoft.co.uk

 * qascript@mail.ru

 *

 * Copyright 2012-2017 Demyanchuk Dmitry (https://vk.com/dmitry.demyanchuk)

 */



class stream extends db_connect

{

    private $requestFrom = 0;



    public function __construct($dbo = NULL)

    {

        parent::__construct($dbo);

    }



    public function getAllCount()

    {

        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE removeAt = 0");

        $stmt->execute();



        return $number_of_rows = $stmt->fetchColumn();

    }



    private function getLikeMaxId()

    {

        $stmt = $this->db->prepare("SELECT MAX(id) FROM likes");

        $stmt->execute();



        return $number_of_rows = $stmt->fetchColumn();

    }



    private function getMaxId()

    {

        $stmt = $this->db->prepare("SELECT MAX(id) FROM posts");

        $stmt->execute();



        return $number_of_rows = $stmt->fetchColumn();

    }



    public function count($language = 'en')

    {

        $count = 0;



        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE accessMode = 0 AND removeAt = 0");



        if ($stmt->execute()) {



            $count = $stmt->fetchColumn();

        }



        return $count;

    }



    public function getFavoritesCount()

    {

        $count = 0;



        $stmt = $this->db->prepare("SELECT count(*) FROM likes WHERE fromUserId = (:fromUserId) AND removeAt = 0");

        $stmt->bindParam(':fromUserId', $this->requestFrom, PDO::PARAM_INT);



        if ($stmt->execute()) {



            $count = $stmt->fetchColumn();

        }



        return $count;

    }



    public function getFavorites($itemId = 0)

    {

        if ($itemId == 0) {



            $itemId = $this->getLikeMaxId();

            $itemId++;

        }



        $result = array("error" => false,

                        "error_code" => ERROR_SUCCESS,

                        "itemId" => $itemId,

                        "items" => array());



        $stmt = $this->db->prepare("SELECT id, postId FROM likes WHERE removeAt = 0 AND id < (:itemId) AND fromUserId = (:fromUserId) ORDER BY id DESC LIMIT 20");

        $stmt->bindParam(':fromUserId', $this->requestFrom, PDO::PARAM_INT);

        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);



        if ($stmt->execute()) {



            if ($stmt->rowCount() > 0) {



                while ($row = $stmt->fetch()) {



                    $post = new post($this->db);

                    $post->setRequestFrom($this->requestFrom);

                    $postInfo = $post->info($row['postId']);

                    unset($post);



                    array_push($result['items'], $postInfo);



                    $result['itemId'] = $row['id'];



                    unset($postInfo);

                }

            }

        }



        return $result;

    }

    ///rjc 2018-4-28
    
    public function lastIndex()

    {

        $stmt = $this->db->prepare("SELECT count(*) FROM posts");

        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn() + 1;

    }

    private function getCount($queryText)

    {

        $queryText = "%".$queryText."%";


        $sql = "SELECT count(*) FROM posts WHERE removeAt = 0 AND (post LIKE '{$queryText}')";


        $stmt = $this->db->prepare($sql);

        $stmt->execute();



        return $number_of_rows = $stmt->fetchColumn();

    }

    public function query($queryText = '',  $itemId = 0)
    {

        $originQuery = $queryText;



        if ($itemId == 0) {

            $itemId = $this->lastIndex();

        }
      

        $endSql = " ORDER BY id DESC LIMIT 20";



        $result = array("error" => false,

                        "error_code" => ERROR_SUCCESS,

                        "itemCount" => $this->getCount($originQuery),

                        "itemId" => $itemId,

                        "query" => $originQuery,

                        "items" => array());



        $queryText = "%".$queryText."%";



        $sql = "SELECT * FROM posts WHERE removeAt = 0 AND post LIKE '{$queryText}' AND id < {$itemId}".$endSql;

        $stmt = $this->db->prepare($sql);

        if ($stmt->execute()) {



            if ($stmt->rowCount() > 0) {


                //////////
                 // while ($row = $stmt->fetch()) {



                 //    array_push($result['items'], $this->info($row));



                 //    $result['itemId'] = $row['id'];

               // }
                //////////////
                 while ($row = $stmt->fetch()) {



                    $post = new post($this->db);

                    $post->setRequestFrom($this->requestFrom);

                    $postInfo = $post->info($row['id']);

                    unset($post);



                    array_push($result['items'], $postInfo);



                    $result['itemId'] = $postInfo['id'];



                    unset($postInfo);

                }

            }

        }



        return $result;

    }

    ///rjc2018-4-28    

    ////rjc backup 

    public function get($itemId = 0, $language = 'en')

    {

        if ($itemId == 0) {



            $itemId = $this->getMaxId();

            $itemId++;

        }



        $result = array("error" => false,

                         "error_code" => ERROR_SUCCESS,

                         "itemId" => $itemId,

                         "items" => array());



        $stmt = $this->db->prepare("SELECT id FROM posts WHERE accessMode = 0 AND groupId = 0 AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");

        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);



        if ($stmt->execute()) {



            if ($stmt->rowCount() > 0) {



                while ($row = $stmt->fetch()) {



                    $post = new post($this->db);

                    $post->setRequestFrom($this->requestFrom);

                    $postInfo = $post->info($row['id']);

                    unset($post);



                    array_push($result['items'], $postInfo);



                    $result['itemId'] = $postInfo['id'];



                    unset($postInfo);

                }

            }

        }



        return $result;

    }

    ///backup end

    //rjc 2018-04-28
    // public function get($itemId = 0, $query = '' , $language = 'en')

    // {

    //     if ($itemId == 0) {



    //         $itemId = $this->getMaxId();

    //         $itemId++;

    //     }



    //     $result = array("error" => false,

    //                      "error_code" => ERROR_SUCCESS,

    //                      "itemId" => $itemId,

    //                      "items" => array());

    //     //rjc
     

    //     if($query == '') 
    //     {

    //         $stmt = $this->db->prepare("SELECT id FROM posts WHERE accessMode = 0 AND groupId = 0 AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");

    //     }   
    //     else
    //     {
    //          $queryText = "%".$query."%";
             
    //          $stmt = $this->db->prepare("SELECT id FROM posts WHERE accessMode = 0 and post LIKE '{$queryText}' AND groupId = 0 AND removeAt = 0 ORDER BY id DESC LIMIT 20");

    //     }
    //     //rjc

        

    //     $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);



    //     if ($stmt->execute()) {



    //         if ($stmt->rowCount() > 0) {



    //             while ($row = $stmt->fetch()) {



    //                 $post = new post($this->db);

    //                 $post->setRequestFrom($this->requestFrom);

    //                 $postInfo = $post->info($row['id']);

    //                 unset($post);



    //                 array_push($result['items'], $postInfo);



    //                 $result['itemId'] = $postInfo['id'];



    //                 unset($postInfo);

    //             }

    //         }

    //     }



    //     return $result;

    // }
    //rjc 2018-04-28



    public function setRequestFrom($requestFrom)

    {

        $this->requestFrom = $requestFrom;

    }



    public function getRequestFrom()

    {

        return $this->requestFrom;

    }

}



