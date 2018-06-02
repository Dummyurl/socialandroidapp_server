<?php

/*!
 * ifsoft.co.uk engine v1.0
 *
 * http://ifsoft.com.ua, http://ifsoft.co.uk
 * qascript@ifsoft.co.uk
 *
 * Copyright 2012-2016 Demyanchuk Dmitry (https://vk.com/dmitry.demyanchuk)
 */

class video extends db_connect
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
        $stmt = $this->db->prepare("SELECT count(*) FROM video");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxIdLikes()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM video_likes");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxId()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM video");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function allCommentsCount()
    {
        $stmt = $this->db->prepare("SELECT max(id) FROM video_comments");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function count()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM video WHERE fromUserId = (:fromUserId) AND removeAt = 0");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function add($mode, $comment, $videoUrl, $imgUrl = "", $itemArea = "", $itemCountry = "", $itemCity = "", $itemLat = "0.00000", $itemLng = "0.00000")
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        if (strlen($videoUrl) == 0 && strlen($imgUrl) == 0) {

            return $result;
        }

        if (strlen($comment) != 0) {

            $comment = $comment." ";
        }

        $currentTime = time();
        $ip_addr = helper::ip_addr();
        $u_agent = helper::u_agent();

        $stmt = $this->db->prepare("INSERT INTO video (fromUserId, accessMode, comment, videoUrl, imgUrl, area, country, city, lat, lng, createAt, ip_addr, u_agent) value (:fromUserId, :accessMode, :comment, :videoUrl, :imgUrl, :area, :country, :city, :lat, :lng, :createAt, :ip_addr, :u_agent)");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":accessMode", $mode, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
        $stmt->bindParam(":videoUrl", $videoUrl, PDO::PARAM_STR);
        $stmt->bindParam(":imgUrl", $imgUrl, PDO::PARAM_STR);
        $stmt->bindParam(":area", $itemArea, PDO::PARAM_STR);
        $stmt->bindParam(":country", $itemCountry, PDO::PARAM_STR);
        $stmt->bindParam(":city", $itemCity, PDO::PARAM_STR);
        $stmt->bindParam(":lat", $itemLat, PDO::PARAM_STR);
        $stmt->bindParam(":lng", $itemLng, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS,
                            "videoId" => $this->db->lastInsertId(),
                            "video" => $this->info($this->db->lastInsertId()));

            $this->update();
        }

        return $result;
    }

    private function update() {

        $account = new account($this->db, $this->requestFrom);
        $account->updateCounters();
        unset($account);
    }

    public function remove($itemId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $itemInfo = $this->info($itemId);

        if ($itemInfo['error'] === true) {

            return $result;
        }

        if ($itemInfo['fromUserId'] != $this->requestFrom) {

            return $result;
        }

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE video SET removeAt = (:removeAt) WHERE id = (:itemId)");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $stmt2 = $this->db->prepare("DELETE FROM notifications WHERE postId = (:postId) AND notifyType > 9");
            $stmt2->bindParam(":postId", $itemId, PDO::PARAM_INT);
            $stmt2->execute();

            //remove all comments to video

            $stmt3 = $this->db->prepare("UPDATE video_comments SET removeAt = (:removeAt) WHERE videoId = (:videoId)");
            $stmt3->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
            $stmt3->bindParam(":videoId", $itemId, PDO::PARAM_INT);
            $stmt3->execute();

            //remove all likes to video

            $stmt4 = $this->db->prepare("UPDATE video_likes SET removeAt = (:removeAt) WHERE videoId = (:videoId) AND removeAt = 0");
            $stmt4->bindParam(":videoId", $itemId, PDO::PARAM_INT);
            $stmt4->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
            $stmt4->execute();

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);

            $this->update();
        }

        return $result;
    }

    public function restore($itemId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $itemInfo = $this->info($itemId);

        if ($itemInfo['error'] === true) {

            return $result;
        }

        $stmt = $this->db->prepare("UPDATE video SET removeAt = 0 WHERE id = (:itemId)");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);
        }

        return $result;
    }

    private function getLikesCount($itemId)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM video_likes WHERE videoId = (:videoId) AND removeAt = 0");
        $stmt->bindParam(":videoId", $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function getCommentsCount($itemId)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM video_comments WHERE videoId = (:itemId) AND removeAt = 0");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function recalculate($itemId) {

        $comments_count = 0;
        $likes_count = 0;
        $rating = 0;

        $likes_count = $this->getLikesCount($itemId);
        $comments_count = $this->getCommentsCount($itemId);

        $rating = $likes_count + $comments_count;

        $stmt = $this->db->prepare("UPDATE video SET likesCount = (:likesCount), commentsCount = (:commentsCount), rating = (:rating) WHERE id = (:itemId)");
        $stmt->bindParam(":likesCount", $likes_count, PDO::PARAM_INT);
        $stmt->bindParam(":commentsCount", $comments_count, PDO::PARAM_INT);
        $stmt->bindParam(":rating", $rating, PDO::PARAM_INT);
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->execute();

        $account = new account($this->db, $this->requestFrom);
        $account->updateCounters();
        unset($account);
    }

    public function like($itemId, $fromUserId)
    {
        $account = new account($this->db, $fromUserId);
        $account->setLastActive();
        unset($account);

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $itemInfo = $this->info($itemId);

        if ($itemInfo['error'] === true) {

            return $result;
        }

        if ($itemInfo['removeAt'] != 0) {

            return $result;
        }

        if ($this->is_like_exists($itemId, $fromUserId)) {

            $removeAt = time();

            $stmt = $this->db->prepare("UPDATE video_likes SET removeAt = (:removeAt) WHERE videoId = (:itemId) AND fromUserId = (:fromUserId) AND removeAt = 0");
            $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
            $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
            $stmt->bindParam(":removeAt", $removeAt, PDO::PARAM_INT);
            $stmt->execute();

            $notify = new notify($this->db);
            $notify->removeNotify($itemInfo['fromUserId'], $fromUserId, NOTIFY_TYPE_VIDEO_LIKE, $itemId);
            unset($notify);

        } else {

            $createAt = time();
            $ip_addr = helper::ip_addr();

            $stmt = $this->db->prepare("INSERT INTO video_likes (toUserId, fromUserId, videoId, createAt, ip_addr) value (:toUserId, :fromUserId, :itemId, :createAt, :ip_addr)");
            $stmt->bindParam(":toUserId", $itemInfo['fromUserId'], PDO::PARAM_INT);
            $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
            $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
            $stmt->bindParam(":createAt", $createAt, PDO::PARAM_INT);
            $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
            $stmt->execute();

            if ($itemInfo['fromUserId'] != $fromUserId) {

                $blacklist = new blacklist($this->db);
                $blacklist->setRequestFrom($itemInfo['fromUserId']);

                if (!$blacklist->isExists($fromUserId)) {

                    $account = new account($this->db, $itemInfo['fromUserId']);

                    if ($account->getAllowLikesGCM() == ENABLE_LIKES_GCM) {

                        $gcm = new gcm($this->db, $itemInfo['fromUserId']);
                        $gcm->setData(GCM_NOTIFY_VIDEO_LIKE, "You have new like", $itemId);
                        $gcm->send();
                    }

                    unset($account);

                    $notify = new notify($this->db);
                    $notify->createNotify($itemInfo['fromUserId'], $fromUserId, NOTIFY_TYPE_VIDEO_LIKE, $itemId);
                    unset($notify);
                }

                unset($blacklist);
            }
        }

        $this->recalculate($itemId);

        $item_info = $this->info($itemId);

        if ($item_info['fromUserId'] != $this->requestFrom) {

            $account = new account($this->db, $item_info['fromUserId']);
            $account->updateCounters();
            unset($account);
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "likesCount" => $item_info['likesCount'],
                        "myLike" => $item_info['myLike']);

        return $result;
    }

    private function is_like_exists($itemId, $fromUserId)
    {
        $stmt = $this->db->prepare("SELECT id FROM video_likes WHERE fromUserId = (:fromUserId) AND videoId = (:itemId) AND removeAt = 0 LIMIT 1");
        $stmt->bindParam(":fromUserId", $fromUserId, PDO::PARAM_INT);
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            return true;
        }

        return false;
    }

    public function getLikers($itemId, $likeId = 0)
    {

        if ($likeId == 0) {

            $likeId = $this->getMaxIdLikes();
            $likeId++;
        }

        $likers = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "likeId" => $likeId,
                        "likers" => array());

        $stmt = $this->db->prepare("SELECT * FROM video_likes WHERE videoId = (:itemId) AND id < (:likeId) AND removeAt = 0 ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
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

    public function info($itemId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM video WHERE id = (:itemId) LIMIT 1");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $time = new language($this->db, $this->language);

                $myLike = false;

                if ($this->requestFrom != 0) {

                    if ($this->is_like_exists($itemId, $this->requestFrom)) {

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
                                "fromUserUsername" => $profileInfo['username'],
                                "fromUserFullname" => $profileInfo['fullname'],
                                "fromUserPhoto" => $profileInfo['lowPhotoUrl'],
                                "fromUserAllowVideoComments" => $profileInfo['allowVideoComments'],
                                "comment" => htmlspecialchars_decode(stripslashes($row['comment'])),
                                "area" => htmlspecialchars_decode(stripslashes($row['area'])),
                                "country" => htmlspecialchars_decode(stripslashes($row['country'])),
                                "city" => htmlspecialchars_decode(stripslashes($row['city'])),
                                "lat" => $row['lat'],
                                "lng" => $row['lng'],
                                "imgUrl" => $row['imgUrl'],
                                "videoUrl" => $row['videoUrl'],
                                "previewImgUrl" => $row['previewImgUrl'],
                                "originImgUrl" => $row['originImgUrl'],
                                "rating" => $row['rating'],
                                "commentsCount" => $row['commentsCount'],
                                "likesCount" => $row['likesCount'],
                                "myLike" => $myLike,
                                "createAt" => $row['createAt'],
                                "date" => date("Y-m-d H:i:s", $row['createAt']),
                                "timeAgo" => $time->timeAgo($row['createAt']),
                                "removeAt" => $row['removeAt']);
            }
        }

        return $result;
    }

    public function get($profileId, $itemId = 0, $accessMode = 0)
    {
        if ($itemId == 0) {

            $itemId = $this->getMaxId();
            $itemId++;
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "itemId" => $itemId,
                        "items" => array());

        if ($accessMode == 0) {

            $stmt = $this->db->prepare("SELECT id FROM video WHERE accessMode = 0 AND fromUserId = (:fromUserId) AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 16");
            $stmt->bindParam(':fromUserId', $profileId, PDO::PARAM_INT);
            $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        } else {

            $stmt = $this->db->prepare("SELECT id FROM video WHERE fromUserId = (:fromUserId) AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 16");
            $stmt->bindParam(':fromUserId', $profileId, PDO::PARAM_INT);
            $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $itemInfo = $this->info($row['id']);

                array_push($result['items'], $itemInfo);

                $result['itemId'] = $itemInfo['id'];

                unset($itemInfo);
            }
        }

        return $result;
    }

    public function commentCreate($itemId, $text, $notifyId = 0, $replyToUserId = 0)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        if (strlen($text) == 0) {

            return $result;
        }

        $itemInfo = $this->info($itemId);

        $currentTime = time();
        $ip_addr = helper::ip_addr();
        $u_agent = helper::u_agent();

        $stmt = $this->db->prepare("INSERT INTO video_comments (fromUserId, replyToUserId, videoId, comment, createAt, notifyId, ip_addr, u_agent) value (:fromUserId, :replyToUserId, :videoId, :comment, :createAt, :notifyId, :ip_addr, :u_agent)");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":replyToUserId", $replyToUserId, PDO::PARAM_INT);
        $stmt->bindParam(":videoId", $itemId, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $text, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":notifyId", $notifyId, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS,
                            "commentId" => $this->db->lastInsertId(),
                            "comment" => $this->commentInfo($this->db->lastInsertId()));

            $account = new account($this->db, $this->requestFrom);
            $account->setLastActive();
            unset($account);

            if (($this->requestFrom != $itemInfo['fromUserId']) && ($replyToUserId != $itemInfo['fromUserId'])) {

                $account = new account($this->db, $itemInfo['fromUserId']);

                if ($account->getAllowCommentsGCM() == ENABLE_COMMENTS_GCM) {

                    $gcm = new gcm($this->db, $itemInfo['fromUserId']);
                    $gcm->setData(GCM_NOTIFY_VIDEO_COMMENT, "You have a new comment.", $itemId);
                    $gcm->send();
                }

                $notify = new notify($this->db);
                $notifyId = $notify->createNotify($itemInfo['fromUserId'], $this->requestFrom, NOTIFY_TYPE_VIDEO_COMMENT, $itemInfo['id']);
                unset($notify);

                $this->commentSetNotifyId($result['commentId'], $notifyId);

                unset($account);
            }

            if ($replyToUserId != $this->requestFrom && $replyToUserId != 0) {

                $account = new account($this->db, $replyToUserId);

                if ($account->getAllowCommentReplyGCM() == 1) {

                    $gcm = new gcm($this->db, $replyToUserId);
                    $gcm->setData(GCM_NOTIFY_VIDEO_COMMENT_REPLY, "You have a new reply to comment.", $itemId);
                    $gcm->send();
                }

                $notify = new notify($this->db);
                $notifyId = $notify->createNotify($replyToUserId, $this->requestFrom, NOTIFY_TYPE_VIDEO_COMMENT_REPLY, $itemInfo['id']);
                unset($notify);

                $this->commentSetNotifyId($result['commentId'], $notifyId);

                unset($account);
            }

            $this->recalculate($itemId);
        }

        return $result;
    }

    private function commentSetNotifyId($commentId, $notifyId)
    {
        $stmt = $this->db->prepare("UPDATE video_comments SET notifyId = (:notifyId) WHERE id = (:commentId)");
        $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
        $stmt->bindParam(":notifyId", $notifyId, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function commentRemove($commentId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $commentInfo = $this->commentInfo($commentId);

        if ($commentInfo['error'] === true) {

            return $result;
        }

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE video_comments SET removeAt = (:removeAt) WHERE id = (:commentId)");
        $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $notify = new notify($this->db);
            $notify->remove($commentInfo['notifyId']);
            unset($notify);

            $this->recalculate($commentInfo['videoId']);

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);
        }

        return $result;
    }

    public function commentRemoveAll($itemId) {

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE video_comments SET removeAt = (:removeAt) WHERE videoId = (:videoId)");
        $stmt->bindParam(":videoId", $itemId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
    }

    public function commentInfo($commentId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM video_comments WHERE id = (:commentId) LIMIT 1");
        $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $time = new language($this->db, $this->language);

                $profile = new profile($this->db, $row['fromUserId']);
                $fromUserId = $profile->get();
                unset($profile);

                $replyToUserId = $row['replyToUserId'];
                $replyToUserUsername = "";
                $replyToFullname = "";

                if ($replyToUserId != 0) {

                    $profile = new profile($this->db, $row['replyToUserId']);
                    $replyToUser = $profile->get();
                    unset($profile);

                    $replyToUserUsername = $replyToUser['username'];
                    $replyToFullname = $replyToUser['fullname'];
                }

                $lowPhotoUrl = "/img/profile_default_photo.png";

                if (strlen($fromUserId['lowPhotoUrl']) != 0) {

                    $lowPhotoUrl = $fromUserId['lowPhotoUrl'];
                }

                $itemInfo = $this->info($row['videoId']);

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "comment" => htmlspecialchars_decode(stripslashes($row['comment'])),
                                "fromUserId" => $row['fromUserId'],
                                "fromUserState" => $fromUserId['state'],
                                "fromUserVerify" => $fromUserId['verify'],
                                "fromUserUsername" => $fromUserId['username'],
                                "fromUserFullname" => $fromUserId['fullname'],
                                "fromUserPhotoUrl" => $lowPhotoUrl,
                                "replyToUserId" => $replyToUserId,
                                "replyToUserUsername" => $replyToUserUsername,
                                "replyToFullname" => $replyToFullname,
                                "videoId" => $row['videoId'],
                                "itemFromUserId" => $itemInfo['fromUserId'],
                                "createAt" => $row['createAt'],
                                "notifyId" => $row['notifyId'],
                                "timeAgo" => $time->timeAgo($row['createAt']));
            }
        }

        return $result;
    }

    public function commentsGet($itemId, $commentId = 0)
    {
        if ($commentId == 0) {

            $commentId = $this->allCommentsCount() + 1;
        }

        $comments = array("error" => false,
                          "error_code" => ERROR_SUCCESS,
                          "commentId" => $commentId,
                          "itemId" => $itemId,
                          "comments" => array());

        $stmt = $this->db->prepare("SELECT id FROM video_comments WHERE videoId = (:itemId) AND id < (:commentId) AND removeAt = 0 ORDER BY id DESC LIMIT 70");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $commentInfo = $this->commentInfo($row['id']);

                array_push($comments['comments'], $commentInfo);

                $comments['commentId'] = $commentInfo['id'];

                unset($commentInfo);
            }
        }

        return $comments;
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
