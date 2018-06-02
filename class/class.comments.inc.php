<?php

/*!
 * ifsoft.co.uk engine v1.0
 *
 * http://ifsoft.com.ua, http://ifsoft.co.uk
 * qascript@ifsoft.co.uk
 *
 * Copyright 2012-2015 Demyanchuk Dmitry (https://vk.com/dmitry.demyanchuk)
 */

class comments extends db_connect
{

	private $requestFrom = 0;
    private $language = 'en';

	public function __construct($dbo = NULL)
    {
		parent::__construct($dbo);
	}

    public function allCommentsCount()
    {
        $stmt = $this->db->prepare("SELECT max(id) FROM comments");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function count($postId)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM comments WHERE postId = (:postId) AND removeAt = 0");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function create($postId, $text, $notifyId = 0, $replyToUserId = 0)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        if (strlen($text) == 0) {

            return $result;
        }

        $post = new post($this->db);

        $postInfo = $post->info($postId);

        $currentTime = time();
        $ip_addr = helper::ip_addr();
        $u_agent = helper::u_agent();

        $stmt = $this->db->prepare("INSERT INTO comments (fromUserId, replyToUserId, postId, comment, createAt, notifyId, ip_addr, u_agent) value (:fromUserId, :replyToUserId, :postId, :comment, :createAt, :notifyId, :ip_addr, :u_agent)");
        $stmt->bindParam(":fromUserId", $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(":replyToUserId", $replyToUserId, PDO::PARAM_INT);
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $text, PDO::PARAM_STR);
        $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
        $stmt->bindParam(":notifyId", $notifyId, PDO::PARAM_INT);
        $stmt->bindParam(":ip_addr", $ip_addr, PDO::PARAM_STR);
        $stmt->bindParam(":u_agent", $u_agent, PDO::PARAM_STR);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS,
                            "commentId" => $this->db->lastInsertId(),
                            "comment" => $this->info($this->db->lastInsertId()));

            $account = new account($this->db, $this->requestFrom);
            $account->setLastActive();
            unset($account);

            if (($this->requestFrom != $postInfo['fromUserId']) && ($replyToUserId != $postInfo['fromUserId'])) {

                $account = new account($this->db, $postInfo['fromUserId']);

                if ($account->getAllowCommentsGCM() == ENABLE_COMMENTS_GCM) {

                    $gcm = new gcm($this->db, $postInfo['fromUserId']);
                    $gcm->setData(GCM_NOTIFY_COMMENT, "You have a new comment.", $postId);
                    $gcm->send();
                }

                $notify = new notify($this->db);
                $notifyId = $notify->createNotify($postInfo['fromUserId'], $this->requestFrom, NOTIFY_TYPE_COMMENT, $postInfo['id']);
                unset($notify);

                $this->setNotifyId($result['commentId'], $notifyId);

                unset($account);
            }

            if ($replyToUserId != $this->requestFrom && $replyToUserId != 0) {

                $account = new account($this->db, $replyToUserId);

                if ($account->getAllowCommentReplyGCM() == 1) {

                    $gcm = new gcm($this->db, $replyToUserId);
                    $gcm->setData(GCM_NOTIFY_COMMENT_REPLY, "You have a new reply to comment.", $postId);
                    $gcm->send();
                }

                $notify = new notify($this->db);
                $notifyId = $notify->createNotify($replyToUserId, $this->requestFrom, NOTIFY_TYPE_COMMENT_REPLY, $postInfo['id']);
                unset($notify);

                unset($account);
            }

            $post->recalculate($postId);
        }

        unset($post);

        return $result;
    }

    private function setNotifyId($commentId, $notifyId)
    {
        $stmt = $this->db->prepare("UPDATE comments SET notifyId = (:notifyId) WHERE id = (:commentId)");
        $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
        $stmt->bindParam(":notifyId", $notifyId, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function remove($commentId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $commentInfo = $this->info($commentId);

        if ($commentInfo['error'] === true) {

            return $result;
        }

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE comments SET removeAt = (:removeAt) WHERE id = (:commentId)");
        $stmt->bindParam(":commentId", $commentId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $notify = new notify($this->db);
            $notify->remove($commentInfo['notifyId']);
            unset($notify);

            $post = new post($this->db);
            $post->recalculate($commentInfo['postId']);
            unset($post);

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);
        }

        return $result;
    }

    public function removeAll($postId) {

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE comments SET removeAt = (:removeAt) WHERE postId = (:postId)");
        $stmt->bindParam(":postId", $postId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
    }

    public function info($commentId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM comments WHERE id = (:commentId) LIMIT 1");
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

                $post = new post($this->db);
                $post->setRequestFrom($this->getRequestFrom());

                $postInfo = $post->info($row['postId']);

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "comment" => htmlspecialchars_decode(stripslashes($row['comment'])),
                                "fromUserId" => $row['fromUserId'],
                                "fromUserState" => $fromUserId['state'],
                                "fromUserVerify" => $fromUserId['verify'],
                                "fromUserOnline" => $fromUserId['online'],
                                "fromUserUsername" => $fromUserId['username'],
                                "fromUserFullname" => $fromUserId['fullname'],
                                "fromUserPhotoUrl" => $lowPhotoUrl,
                                "replyToUserId" => $replyToUserId,
                                "replyToUserUsername" => $replyToUserUsername,
                                "replyToFullname" => $replyToFullname,
                                "postId" => $row['postId'],
                                "postFromUserId" => $postInfo['fromUserId'],
                                "createAt" => $row['createAt'],
                                "notifyId" => $row['notifyId'],
                                "timeAgo" => $time->timeAgo($row['createAt']));
            }
        }

        return $result;
    }

    public function get($postId, $commentId = 0)
    {
        if ($commentId == 0) {

            $commentId = $this->allCommentsCount() + 1;
        }

        $comments = array("error" => false,
                         "error_code" => ERROR_SUCCESS,
                         "commentId" => $commentId,
                         "postId" => $postId,
                         "comments" => array());

        $stmt = $this->db->prepare("SELECT id FROM comments WHERE postId = (:postId) AND id < (:commentId) AND removeAt = 0 ORDER BY id DESC LIMIT 70");
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $commentInfo = $this->info($row['id']);

                array_push($comments['comments'], $commentInfo);

                $comments['commentId'] = $commentInfo['id'];

                unset($commentInfo);
            }
        }

        return $comments;
    }

    public function getPreview($postId)
    {
        $commentId = $this->allCommentsCount() + 1;

        $comments = array("error" => false,
                          "error_code" => ERROR_SUCCESS,
                          "commentId" => $commentId,
                          "postId" => $postId,
                          "count" => $this->count($postId),
                          "comments" => array());

        $stmt = $this->db->prepare("SELECT id FROM comments WHERE postId = (:postId) AND id < (:commentId) AND removeAt = 0 ORDER BY id DESC LIMIT 3");
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $commentInfo = $this->info($row['id']);

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
}
