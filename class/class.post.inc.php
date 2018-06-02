<?php

/*!
 * ifsoft.co.uk engine v1.0
 *
 * http://ifsoft.com.ua, http://ifsoft.co.uk
 * raccoonsquare@gmail.com
 *
 * Copyright 2012-2018 Demyanchuk Dmitry (raccoonsquare@gmail.com)
 */

class post extends db_connect
{
	private $requestFrom = 0;
    private $language = 'en';
    private $profileId = 0;

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

    public function getAllCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM posts");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxIdLikes()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM likes");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxIdPosts()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM posts");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function count()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function add($mode, $postText, $postImage = "", $rePostId = 0, $groupId = 0, $postArea = "", $postCountry = "", $postCity = "", $postLat = "0.00000", $postLng = "0.00000", $videoImgUrl = "", $videoUrl = "")
    {

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $ip_addr = helper::ip_addr();

        $stoplist = new stoplist($this->db);

        if ($stoplist->isExists($ip_addr)) {

            return $result;
        }

        unset($stoplist);

        $account = new account($this->db, $this->requestFrom);
        $account->setLastActive();
        unset($account);

        $urlData = $postText;

        $urlTitle = "";
        $urlDescription = "";
        $urlImage = "";
        $urlLink = "";

        if (preg_match('@(?<=^|(?<=[^a-zA-Z0-9-_\.//]))((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.\,]*(\?\S+)?)?)*)@', htmlspecialchars_decode(stripslashes($urlData)), $results)) {

            $page = $results[0];

            $page = $this->addScheme($page);

            $content = file_get_contents("https://api.urlmeta.org/?url=".$page);

            $data = json_decode($content, true);

            $urlResult = $data['result'];
            $urlResponse = $data['meta'];

            if ($urlResult['status'] === "OK") {

                $urlLink = $page;

                if (isset($urlResponse['image'])) {

                    $urlImage = $urlResponse['image'];
                }

                if (isset($urlResponse['description'])) {

                    $urlDescription = $urlResponse['description'];
                }

                if (isset($urlResponse['title'])) {

                    $urlTitle = $urlResponse['title'];
                }
            }
        }

        $urlTitle = helper::clearText($urlTitle);
        $urlTitle = helper::escapeText($urlTitle);

        $urlDescription = helper::clearText($urlDescription);
        $urlDescription = helper::escapeText($urlDescription);

        if (strlen($postText) == 0 && strlen($postImage) == 0 && $rePostId == 0 && strlen($videoUrl) == 0) {

            return $result;
        }

        if ($rePostId != 0) {

            $rePostInfo = $this->info($rePostId);

            if ($rePostInfo['error'] === true || $rePostInfo['removeAt'] != 0 || $rePostInfo['fromUserId'] == $this->requestFrom) {

                return $result;
            }
        }

        if (strlen($postText) != 0) {

            $postText = $postText . " ";
        }

        $currentTime = time();
        $u_agent = helper::u_agent();

        $deviceType = 0;

        if (strpos($u_agent, "Android") !== false) {

            $deviceType = 1;

        } else if (strpos($u_agent, "Darwin") !== false) {

            $deviceType = 2;

        } else {

            $deviceType = 0;
        }

        $stmt = $this->db->prepare("INSERT INTO posts (fromUserId, accessMode, rePostId, groupId, post, urlPreviewTitle, urlPreviewImage, urlPreviewDescription, urlPreviewLink, area, country, city, lat, lng, imgUrl, previewVideoImgUrl, videoUrl, createAt, ip_addr, u_agent, deviceType) value (:fromUserId, :accessMode, :rePostId, :groupId, :post, :urlPreviewTitle, :urlPreviewImage, :urlPreviewDescription, :urlPreviewLink, :area, :country, :city, :lat, :lng, :imgUrl, :videoImgUrl, :videoUrl, :createAt, :ip_addr, :u_agent, :deviceType)");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":accessMode", $mode, PDO::PARAM_INT);
        $stmt->bindParam(":rePostId", $rePostId, PDO::PARAM_INT);
        $stmt->bindParam(":groupId", $groupId, PDO::PARAM_INT);
        $stmt->bindParam(":post", $postText, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewTitle", $urlTitle, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewImage", $urlImage, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewDescription", $urlDescription, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewLink", $urlLink, PDO::PARAM_STR);
        $stmt->bindParam(":area", $postArea, PDO::PARAM_STR);
        $stmt->bindParam(":country", $postCountry, PDO::PARAM_STR);
        $stmt->bindParam(":city", $postCity, PDO::PARAM_STR);
        $stmt->bindParam(":lat", $postLat, PDO::PARAM_STR);
        $stmt->bindParam(":lng", $postLng, PDO::PARAM_STR);
        $stmt->bindParam(":imgUrl", $postImage, PDO::PARAM_STR);
        $stmt->bindParam(":videoImgUrl", $videoImgUrl, PDO::PARAM_STR);
        $stmt->bindParam(":videoUrl", $videoUrl, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);
        $stmt->bindParam(":deviceType", $deviceType, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS,
                            "postId" => $this->db->lastInsertId(),
                            "post" => $this->info($this->db->lastInsertId()));

            if ($rePostId != 0) {

                $this->recalculate($rePostId);
            }

            if ($groupId != 0) {

                $group = new group($this->db, $groupId);
                $group->updateCounters();
                unset($group);

            } else {

                $account = new account($this->db, $this->requestFrom);
                $account->updateCounters();
                unset($account);
            }
        }

        return $result;
    }

    public function removeByIp($ip_addr)
    {

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN,
                        "count" => 0);

        $stmt = $this->db->prepare("SELECT id FROM posts WHERE ip_addr = (:ip_addr) AND removeAt = 0");
        $stmt->bindParam(':ip_addr', $ip_addr, PDO::PARAM_STR);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $this->remove($row['id']);
            }

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS,
                            "count" => $stmt->rowCount());
        }

        return $result;
    }

    public function remove($postId)
    {
        $result = array("error" => true);

        $postInfo = $this->info($postId);

        if ($postInfo['error'] === true) {

            return $result;
        }

        if ($postInfo['fromUserId'] != $this->requestFrom) {

            $error = true;

            if ($postInfo['groupId'] != 0) {

                $group = new group($this->db, $postInfo['groupId']);
                $group->setRequestFrom($this->requestFrom);

                $groupInfo = $group->get();

                if ($groupInfo['accountAuthor'] == $this->requestFrom) {

                    $error = false;
                }

                unset($group);
            }

            if ($error) return $result;
        }

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE posts SET removeAt = (:removeAt) WHERE id = (:postId)");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            // remove all notifications by likes and comments

            $stmt2 = $this->db->prepare("DELETE FROM notifications WHERE postId = (:postId) AND notifyType < 7");
            $stmt2->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt2->execute();

            //remove all comments to post

            $stmt3 = $this->db->prepare("UPDATE comments SET removeAt = (:removeAt) WHERE postId = (:postId)");
            $stmt3->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
            $stmt3->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt3->execute();

            //remove all likes to post

            $stmt4 = $this->db->prepare("UPDATE likes SET removeAt = (:removeAt) WHERE postId = (:postId) AND removeAt = 0");
            $stmt4->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt4->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
            $stmt4->execute();

            $result = array("error" => false);
        }

        if ($postInfo['groupId'] != 0) {

            $group = new group($this->db, $postInfo['groupId']);
            $group->setRequestFrom($this->requestFrom);

            $group->updateCounters();

            unset($group);

        } else {

            $this->recalculate($postId);
        }

        if ($postInfo['rePostId'] != 0) {

            $this->recalculate($postInfo['rePostId']);
        }

        return $result;
    }

    public function restore($postId)
    {
        $result = array("error" => true);

        $postInfo = $this->info($postId);

        if ($postInfo['error'] === true) {

            return $result;
        }

        $stmt = $this->db->prepare("UPDATE posts SET removeAt = 0 WHERE id = (:postId)");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array("error" => false);
        }

        return $result;
    }

    public function edit($postId, $postText = "", $postImage = "")
    {
        $urlData = $postText;

        $urlTitle = "";
        $urlDescription = "";
        $urlImage = "";
        $urlLink = "";

        if (preg_match('@(?<=^|(?<=[^a-zA-Z0-9-_\.//]))((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.\,]*(\?\S+)?)?)*)@', htmlspecialchars_decode(stripslashes($urlData)), $results)) {

            $page = $results[0];

            $page = $this->addScheme($page);

            $content = file_get_contents("https://api.urlmeta.org/?url=".$page);

            $data = json_decode($content, true);

            $urlResult = $data['result'];
            $urlResponse = $data['meta'];

            if ($urlResult['status'] === "OK") {

                $urlLink = $results[0];

                if (isset($urlResponse['image'])) {

                    $urlImage = $urlResponse['image'];
                }

                if (isset($urlResponse['description'])) {

                    $urlDescription = $urlResponse['description'];
                }

                if (isset($urlResponse['title'])) {

                    $urlTitle = $urlResponse['title'];
                }
            }
        }

        $urlTitle = helper::clearText($urlTitle);
        $urlTitle = helper::escapeText($urlTitle);

        $urlDescription = helper::clearText($urlDescription);
        $urlDescription = helper::escapeText($urlDescription);

        $result = array("error" => true);

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE posts SET post = (:postText), imgUrl = (:imgUrl), urlPreviewTitle = (:urlPreviewTitle), urlPreviewImage = (:urlPreviewImage), urlPreviewDescription = (:urlPreviewDescription), urlPreviewLink = (:urlPreviewLink), createAt = (:createAt) WHERE id = (:postId)");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->bindParam(":postText", $postText, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewTitle", $urlTitle, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewImage", $urlImage, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewDescription", $urlDescription, PDO::PARAM_STR);
        $stmt->bindParam(":urlPreviewLink", $urlLink, PDO::PARAM_STR);
        $stmt->bindParam(":imgUrl", $postImage, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array("error" => false);
        }

        return $result;
    }

    public function like($postId, $fromUserId)
    {
        $account = new account($this->db, $fromUserId);
        $account->setLastActive();
        unset($account);

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $postInfo = $this->info($postId);

        if ($postInfo['error'] === true) {

            return $result;
        }

        if ($postInfo['removeAt'] != 0) {

            return $result;
        }

        if ($this->is_like_exists($postId, $fromUserId)) {

            $removeAt = time();

            $stmt = $this->db->prepare("UPDATE likes SET removeAt = (:removeAt) WHERE postId = (:postId) AND fromUserId = (:fromUserId) AND removeAt = 0");
            $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
            $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt->bindParam(":removeAt", $removeAt, PDO::PARAM_INT);
            $stmt->execute();

            $notify = new notify($this->db);
            $notify->removeNotify($postInfo['fromUserId'], $fromUserId, NOTIFY_TYPE_LIKE, $postId);
            unset($notify);

        } else {

            $createAt = time();
            $ip_addr = helper::ip_addr();

            $stmt = $this->db->prepare("INSERT INTO likes (toUserId, fromUserId, postId, createAt, ip_addr) value (:toUserId, :fromUserId, :postId, :createAt, :ip_addr)");
            $stmt->bindParam(":toUserId", $postInfo['fromUserId'], PDO::PARAM_INT);
            $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
            $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
            $stmt->bindParam(":createAt", $createAt, PDO::PARAM_INT);
            $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
            $stmt->execute();

            if ($postInfo['fromUserId'] != $fromUserId) {

                $blacklist = new blacklist($this->db);
                $blacklist->setRequestFrom($postInfo['fromUserId']);

                if (!$blacklist->isExists($fromUserId)) {

                    $account = new account($this->db, $postInfo['fromUserId']);

                    if ($account->getAllowLikesGCM() == ENABLE_LIKES_GCM) {

                        $gcm = new gcm($this->db, $postInfo['fromUserId']);
                        $gcm->setData(GCM_NOTIFY_LIKE, "You have new like", $postId);
                        $gcm->send();
                    }

                    unset($account);

                    $notify = new notify($this->db);
                    $notify->createNotify($postInfo['fromUserId'], $fromUserId, NOTIFY_TYPE_LIKE, $postId);
                    unset($notify);
                }

                unset($blacklist);
            }
        }

        $this->recalculate($postId);

        $post_info = $this->info($postId);

        if ($post_info['fromUserId'] != $this->requestFrom) {

            $account = new account($this->db, $post_info['fromUserId']);
            $account->updateCounters();
            unset($account);
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "likesCount" => $post_info['likesCount'],
                        "myLike" => $post_info['myLike']);

        return $result;
    }

    private function getLikesCount($postId)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM likes WHERE postId = (:postId) AND removeAt = 0");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function is_like_exists($postId, $fromUserId)
    {
        $stmt = $this->db->prepare("SELECT id FROM likes WHERE fromUserId = (:fromUserId) AND postId = (:postId) AND removeAt = 0 LIMIT 1");
        $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            return true;
        }

        return false;
    }

    private function is_repost_exists($postId, $fromUserId)
    {
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE fromUserId = (:fromUserId) AND rePostId = (:rePostId) AND removeAt = 0 LIMIT 1");
        $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
        $stmt->bindParam(":rePostId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            return true;
        }

        return false;
    }

    private function getRePostsCount($postId)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM posts WHERE rePostId = (:rePostId) AND removeAt = 0");
        $stmt->bindParam(":rePostId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function recalculate($postId) {

        $comments_count = 0;
        $likes_count = 0;
        $reposts_count = 0;
        $rating = 0;

        $likes_count = $this->getLikesCount($postId);

        $reposts_count = $this->getRePostsCount($postId);

        $comments = new comments($this->db);
        $comments_count = $comments->count($postId);
        unset($comments);

        $rating = $likes_count + $comments_count + $reposts_count;

        $stmt = $this->db->prepare("UPDATE posts SET likesCount = (:likesCount), commentsCount = (:commentsCount), rePostsCount = (:rePostsCount), rating = (:rating) WHERE id = (:postId)");
        $stmt->bindParam(":likesCount", $likes_count, PDO::PARAM_INT);
        $stmt->bindParam(":commentsCount", $comments_count, PDO::PARAM_INT);
        $stmt->bindParam(":rePostsCount", $reposts_count, PDO::PARAM_INT);
        $stmt->bindParam(":rating", $rating, PDO::PARAM_INT);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        $account = new account($this->db, $this->requestFrom);
        $account->updateCounters();
        unset($account);
    }

    public function info($postId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = (:postId) LIMIT 1");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $time = new language($this->db, $this->language);

                $myLike = false;
                $myRePost = false;

                if ($this->requestFrom != 0) {

                    if ($this->is_like_exists($postId, $this->requestFrom)) {

                        $myLike = true;
                    }

                    if ($this->is_repost_exists($postId, $this->requestFrom)) {

                        $myRePost = true;
                    }
                }

                $groupAllowComments = 0;
                $groupAuthor = 0;

                if ($row['groupId'] != 0) {

                    $group = new group($this->db, $row['groupId']);
                    $group->setRequestFrom($this->requestFrom);

                    $groupInfo = $group->get();

                    $groupAllowComments = $groupInfo['allowComments'];
                    $groupAuthor = $groupInfo['accountAuthor'];

                    unset($group);
                }

                $profile = new profile($this->db, $row['fromUserId']);
                $profileInfo = $profile->get();
                unset($profile);

                $you_tube_video_img = "";
                $you_tube_video_code = "";
                $you_tube_video_url = "";

                if (preg_match('/(?:http?:\/\/)?(?:www\.)?youtu(?:\.be|be\.com)\/(?:watch\?v=)?([\w\-]{6,12})(?:\&.+)?/i', htmlspecialchars_decode(stripslashes($row['post'])), $results)) {

                    $you_tube_video_img = "http://img.youtube.com/vi/".$results[1]."/0.jpg";
                    $you_tube_video_url = "http://www.youtube.com/v/".$results[1];
                    $you_tube_video_code = $results[1];
                }

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "accessMode" => $row['accessMode'],
                                "deviceType" => $row['deviceType'],
                                "rePostId" => $row['rePostId'],
                                "groupId" => $row['groupId'],
                                "fromUserId" => $row['fromUserId'],
                                "fromUserVerify" => $profileInfo['verify'],
                                "fromUserOnline" => $profileInfo['online'],
                                "fromUserUsername" => $profileInfo['username'],
                                "fromUserFullname" => $profileInfo['fullname'],
                                "fromUserPhoto" => $profileInfo['lowPhotoUrl'],
                                "post" => htmlspecialchars_decode(stripslashes($row['post'])),
                                "area" => htmlspecialchars_decode(stripslashes($row['area'])),
                                "country" => htmlspecialchars_decode(stripslashes($row['country'])),
                                "city" => htmlspecialchars_decode(stripslashes($row['city'])),
                                "YouTubeVideoImg" => $you_tube_video_img,
                                "YouTubeVideoCode" => $you_tube_video_code,
                                "YouTubeVideoUrl" => $you_tube_video_url,
                                "urlPreviewTitle" => htmlspecialchars_decode(stripslashes($row['urlPreviewTitle'])),
                                "urlPreviewImage" => $row['urlPreviewImage'],
                                "urlPreviewLink" => $row['urlPreviewLink'],
                                "urlPreviewDescription" => htmlspecialchars_decode(stripslashes($row['urlPreviewDescription'])),
                                "previewVideoImgUrl" => $row['previewVideoImgUrl'],
                                "videoUrl" => $row['videoUrl'],
                                "lat" => $row['lat'],
                                "lng" => $row['lng'],
                                "imgUrl" => $row['imgUrl'],
                                "allowComments" => $profileInfo['allowComments'],
                                "groupAllowComments" => $groupAllowComments,
                                "groupAuthor" => $groupAuthor,
                                "rating" => $row['rating'],
                                "commentsCount" => $row['commentsCount'],
                                "likesCount" => $row['likesCount'],
                                "viewsCount" => $row['viewsCount'],
                                "rePostsCount" => $row['rePostsCount'],
                                "myLike" => $myLike,
                                "myRePost" => $myRePost,
                                "moderateAt" => $row['moderateAt'],
                                "createAt" => $row['createAt'],
                                "date" => date("Y-m-d H:i:s", $row['createAt']),
                                "timeAgo" => $time->timeAgo($row['createAt']),
                                "removeAt" => $row['removeAt'],
                                "ip_addr" => $row['ip_addr'],
                                "rePost" => array());

                if ($row['rePostId'] != 0) {

                    array_push($result['rePost'], $this->repost_info($row['rePostId']));

                } else {

                    array_push($result['rePost'], array("error" => true,
                                                        "error_code" => ERROR_UNKNOWN));
                }
            }
        }

        return $result;
    }

    public function repost_info($postId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = (:postId) LIMIT 1");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $time = new language($this->db, $this->language);

                $myLike = false;

                if ($this->requestFrom != 0) {

                    if ($this->is_like_exists($postId, $this->requestFrom)) {

                        $myLike = true;
                    }
                }

                $profile = new profile($this->db, $row['fromUserId']);
                $profileInfo = $profile->get();
                unset($profile);

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "accessMode" => $row['accessMode'],
                                "fromUserId" => $row['fromUserId'],
                                "fromUserVerify" => $profileInfo['verify'],
                                "fromUserOnline" => $profileInfo['online'],
                                "fromUserUsername" => $profileInfo['username'],
                                "fromUserFullname" => $profileInfo['fullname'],
                                "fromUserPhoto" => $profileInfo['lowPhotoUrl'],
                                "post" => htmlspecialchars_decode(stripslashes($row['post'])),
                                "area" => htmlspecialchars_decode(stripslashes($row['area'])),
                                "country" => htmlspecialchars_decode(stripslashes($row['country'])),
                                "city" => htmlspecialchars_decode(stripslashes($row['city'])),
                                "lat" => $row['lat'],
                                "lng" => $row['lng'],
                                "imgUrl" => $row['imgUrl'],
                                "allowComments" => $profileInfo['allowComments'],
                                "rating" => $row['rating'],
                                "commentsCount" => $row['commentsCount'],
                                "likesCount" => $row['likesCount'],
                                "rePostsCount" => $row['rePostsCount'],
                                "myLike" => $myLike,
                                "createAt" => $row['createAt'],
                                "date" => date("Y-m-d H:i:s", $row['createAt']),
                                "timeAgo" => $time->timeAgo($row['createAt']),
                                "removeAt" => $row['removeAt']);
            }
        }

        return $result;
    }

    public function get($profileId, $postId = 0, $accessMode = 0)
    {
        if ($postId == 0) {

            $postId = $this->getMaxIdPosts();
            $postId++;
        }

        $posts = array("error" => false,
                       "error_code" => ERROR_SUCCESS,
                       "postId" => $postId,
                       "posts" => array());

        if ($accessMode == 0) {

            $stmt = $this->db->prepare("SELECT id FROM posts WHERE accessMode = 0 AND fromUserId = (:fromUserId) AND groupId = 0 AND removeAt = 0 AND id < (:postId) ORDER BY id DESC LIMIT 20");
            $stmt->bindParam(':fromUserId', $profileId, PDO::PARAM_INT);
            $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);

        } else {

            $stmt = $this->db->prepare("SELECT id FROM posts WHERE fromUserId = (:fromUserId) AND groupId = 0 AND removeAt = 0 AND id < (:postId) ORDER BY id DESC LIMIT 20");
            $stmt->bindParam(':fromUserId', $profileId, PDO::PARAM_INT);
            $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $postInfo = $this->info($row['id']);

                array_push($posts['posts'], $postInfo);

                $posts['postId'] = $postInfo['id'];

                unset($postInfo);
            }
        }

        return $posts;
    }

    public function getLikers($postId, $likeId = 0)
    {

        if ($likeId == 0) {

            $likeId = $this->getMaxIdLikes();
            $likeId++;
        }

        $likers = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "likeId" => $likeId,
                        "likers" => array());

        $stmt = $this->db->prepare("SELECT * FROM likes WHERE postId = (:postId) AND id < (:likeId) AND removeAt = 0 ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':likeId', $likeId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $profile = new profile($this->db, $row['fromUserId']);
                    $profile->setRequestFrom($this->requestFrom);
                    $profileInfo = $profile->get();
                    unset($profile);

                    array_push($likers['likers'], $profileInfo);

                    $likers['likeId'] = $row['id'];
                }
            }
        }

        return $likers;
    }

    public function query($queryText = '', $addedToListAt = 0)
    {
        $originQuery = $queryText;

        if ($addedToListAt == 0) {

            $addedToListAt = time();
        }

        $questions = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "addedToListAt" => $addedToListAt,
                        "query" => $originQuery,
                        "questions" => array());

        $queryText = "%".$queryText."%";

        $stmt = $this->db->prepare("SELECT id FROM qa WHERE question LIKE (:query) AND replyAt = 0 AND removeAt = 0 AND addedToListAt < (:addedToListAt) ORDER BY addedToListAt DESC LIMIT 50");
        $stmt->bindParam(':query', $queryText, PDO::PARAM_STR);
        $stmt->bindParam(':addedToListAt', $addedToListAt, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $questionInfo = $this->info($row['id']);

                    array_push($questions['questions'], $questionInfo);

                    $questions['addedToListAt'] = $questionInfo['addedToListAt'];
                }
            }
        }

        return $questions;
    }

    private function addScheme($url, $scheme = 'http://')
    {
        return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setRequestFrom($requestFrom)
    {
        $this->requestFrom = $requestFrom;
    }

    public function getRequestFrom()
    {
        return $this->requestFrom;
    }

    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }

    public function getProfileId()
    {
        return $this->profileId;
    }
}
