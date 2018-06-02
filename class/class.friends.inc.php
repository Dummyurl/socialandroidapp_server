<?php

/*!
 * ifsoft.co.uk engine v1.0
 *
 * http://ifsoft.com.ua, http://ifsoft.co.uk
 * qascript@ifsoft.co.uk
 *
 * Copyright 2012-2017 Demyanchuk Dmitry (https://vk.com/dmitry.demyanchuk)
 */

class friends extends db_connect
{
	private $requestFrom = 0;
    private $language = 'en';
    private $profileId = 0;

	public function __construct($dbo = NULL, $profileId = 0)
    {
		parent::__construct($dbo);

        $this->setProfileId($profileId);
	}

    public function getAllCount()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM friends");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxId()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM friends");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    private function getMaxIdFollowersTable()
    {
        $stmt = $this->db->prepare("SELECT MAX(id) FROM profile_followers");
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function count()
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM friends WHERE friendTo = (:profileId) AND removeAt = 0");
        $stmt->bindParam(":profileId", $this->profileId, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
    }

    public function reject($friendId) {

        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $my_profile = new profile($this->db, $this->profileId);

        if ($my_profile->is_follower_exists($friendId)) {

            $my_profile->addFollower($friendId);

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);
        }

        unset($my_profile);

        return $result;
    }

    public function accept($friendId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $my_profile = new profile($this->db, $this->profileId);

        if ($my_profile->is_follower_exists($friendId)) {

            $currentTime = time();

            $stmt = $this->db->prepare("INSERT INTO friends (friend, friendTo, createAt) value (:friend, :friendTo, :createAt)");
            $stmt->bindParam(":friend", $friendId, PDO::PARAM_INT);
            $stmt->bindParam(":friendTo", $this->profileId, PDO::PARAM_INT);
            $stmt->bindParam(":createAt", $currentTime, PDO::PARAM_INT);

            if ($stmt->execute()) {

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "itemId" => $this->db->lastInsertId());

                $stmt2 = $this->db->prepare("INSERT INTO friends (friend, friendTo, createAt) value (:friend, :friendTo, :createAt)");
                $stmt2->bindParam(":friend", $this->profileId, PDO::PARAM_INT);
                $stmt2->bindParam(":friendTo", $friendId, PDO::PARAM_INT);
                $stmt2->bindParam(":createAt", $currentTime, PDO::PARAM_INT);
                $stmt2->execute();

                $stmt3 = $this->db->prepare("DELETE FROM profile_followers WHERE follower = (:follower) AND follow_to = (:follow_to)");
                $stmt3->bindParam(":follower", $friendId, PDO::PARAM_INT);
                $stmt3->bindParam(":follow_to", $this->profileId, PDO::PARAM_INT);
                $stmt3->execute();

                $stmt4 = $this->db->prepare("DELETE FROM profile_followers WHERE follower = (:follower) AND follow_to = (:follow_to)");
                $stmt4->bindParam(":follower", $this->profileId, PDO::PARAM_INT);
                $stmt4->bindParam(":follow_to", $friendId, PDO::PARAM_INT);
                $stmt4->execute();

                $stmt5 = $this->db->prepare("DELETE FROM notifications WHERE notifyToId = (:notifyToId) AND notifyFromId = (:notifyFromId) AND notifyType = 1");
                $stmt5->bindParam(":notifyToId", $this->profileId, PDO::PARAM_INT);
                $stmt5->bindParam(":notifyFromId", $friendId, PDO::PARAM_INT);
                $stmt5->execute();

                $stmt5 = $this->db->prepare("DELETE FROM notifications WHERE notifyToId = (:notifyToId) AND notifyFromId = (:notifyFromId) AND notifyType = 1");
                $stmt5->bindParam(":notifyToId", $friendId, PDO::PARAM_INT);
                $stmt5->bindParam(":notifyFromId", $this->profileId, PDO::PARAM_INT);
                $stmt5->execute();

                $account = new account($this->db, $this->profileId);
                $friendsCount = $account->getFriendsCount();
                $account->setFriendsCount($friendsCount);
                unset($friendsCount);
                unset($account);

                $account = new account($this->db, $friendId);
                $friendsCount = $account->getFriendsCount();
                $account->setFriendsCount($friendsCount);


                if ($account->getAllowFollowersGCM() == ENABLE_FOLLOWERS_GCM) {

                    // GCM_FRIEND_REQUEST_ACCEPTED = 12

                    $gcm = new gcm($this->db, $friendId);
                    $gcm->setData(12, "Friend Request accepted", 0);
                    $gcm->send();
                }

                unset($friendsCount);
                unset($account);
            }
        }

        unset($my_profile);

        return $result;
    }

    public function clear()
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $currentTime = time();

        $stmt = $this->db->prepare("UPDATE friends SET removeAt = (:removeAt) WHERE friendTo = (:friendTo) AND removeAt = 0");
        $stmt->bindParam(":friendTo", $this->profileId, PDO::PARAM_INT);
        $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

        if ($stmt->execute()) {

            $result = array("error" => false,
                            "error_code" => ERROR_SUCCESS);
        }

        return $result;
    }

    public function remove($friendId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $my_profile = new profile($this->db, $this->profileId);

        if ($my_profile->is_friend_exists($friendId)) {

            $currentTime = time();

            $stmt = $this->db->prepare("UPDATE friends SET removeAt = (:removeAt) WHERE friendTo = (:friendTo) AND friend = (:friend) AND removeAt = 0");
            $stmt->bindParam(":friendTo", $this->profileId, PDO::PARAM_INT);
            $stmt->bindParam(":friend", $friendId, PDO::PARAM_INT);
            $stmt->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);

            if ($stmt->execute()) {

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS);

                $stmt2 = $this->db->prepare("UPDATE friends SET removeAt = (:removeAt) WHERE friend = (:friend) AND friendTo = (:friendTo) AND removeAt = 0");
                $stmt2->bindParam(":friend", $this->profileId, PDO::PARAM_INT);
                $stmt2->bindParam(":friendTo", $friendId, PDO::PARAM_INT);
                $stmt2->bindParam(":removeAt", $currentTime, PDO::PARAM_INT);
                $stmt2->execute();

                $account = new account($this->db, $this->profileId);
                $account->updateCounters();
                unset($account);

                $account = new account($this->db, $friendId);
                $account->updateCounters();
            }
        }

        return $result;
    }

    public function info($itemId)
    {
        $result = array("error" => true,
                        "error_code" => ERROR_UNKNOWN);

        $stmt = $this->db->prepare("SELECT * FROM friends WHERE id = (:itemId) LIMIT 1");
        $stmt->bindParam(":itemId", $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $row = $stmt->fetch();

                $profile = new profile($this->db, $row['friend']);
                $profileInfo = $profile->getShort();
                unset($profile);

                $result = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "friendUserId" => $row['friend'],
                                "friendUserVerify" => $profileInfo['verify'],
                                "friendUserUsername" => $profileInfo['username'],
                                "friendUserFullname" => $profileInfo['fullname'],
                                "friendUserPhoto" => $profileInfo['lowPhotoUrl'],
                                "friendUserOnline" => $profileInfo['online'],
                                "friendLocation" => $profileInfo['location'],
                                "friendTo" => $row['friendTo'],
                                "friend" => $row['friend'],
                                "createAt" => $row['createAt'],
                                "date" => date("Y-m-d H:i:s", $row['createAt']),
                                "timeAgo" => $profileInfo['lastAuthorizeTimeAgo'],
                                "removeAt" => $row['removeAt']);
            }
        }

        return $result;
    }

    public function get($itemId = 0)
    {
        if ($itemId == 0) {

            $itemId = $this->getMaxId();
            $itemId++;
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "itemId" => $itemId,
                        "items" => array());

        $stmt = $this->db->prepare("SELECT * FROM friends WHERE friendTo = (:friendTo) AND removeAt = 0 AND id < (:itemId) ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':friendTo', $this->profileId, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $time = new language($this->db, $this->language);

                $profile = new profile($this->db, $row['friend']);
                $profileInfo = $profile->getShort();
                unset($profile);

                $itemInfo = array("error" => false,
                                "error_code" => ERROR_SUCCESS,
                                "id" => $row['id'],
                                "friendUserId" => $row['friend'],
                                "friendUserVerify" => $profileInfo['verify'],
                                "friendUserUsername" => $profileInfo['username'],
                                "friendUserFullname" => $profileInfo['fullname'],
                                "friendUserPhoto" => $profileInfo['lowPhotoUrl'],
                                "friendUserOnline" => $profileInfo['online'],
                                "friendLocation" => $profileInfo['location'],
                                "friendTo" => $row['friendTo'],
                                "friend" => $row['friend'],
                                "createAt" => $row['createAt'],
                                "date" => date("Y-m-d H:i:s", $row['createAt']),
                                "timeAgo" => $profileInfo['lastAuthorizeTimeAgo'],
                                "removeAt" => $row['removeAt']);

                array_push($result['items'], $itemInfo);

                $result['itemId'] = $itemInfo['id'];

                unset($itemInfo);
            }
        }

        return $result;
    }

    public function getOnline()
    {
        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "items" => array());

        $stmt = $this->db->prepare("SELECT * FROM friends WHERE friendTo = (:friendTo) AND removeAt = 0 ORDER BY id DESC");
        $stmt->bindParam(':friendTo', $this->profileId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            while ($row = $stmt->fetch()) {

                $profile = new profile($this->db, $row['friend']);
                $profileInfo = $profile->getVeryShort();
                unset($profile);

                if ($profileInfo['online']) {

                    $itemInfo = array("error" => false,
                                      "error_code" => ERROR_SUCCESS,
                                      "id" => $row['id'],
                                      "friendUserId" => $row['friend'],
                                      "friendUserVerify" => $profileInfo['verify'],
                                      "friendUserUsername" => $profileInfo['username'],
                                      "friendUserFullname" => $profileInfo['fullname'],
                                      "friendUserPhoto" => $profileInfo['lowPhotoUrl'],
                                      "friendUserOnline" => $profileInfo['online'],
                                      "friendLocation" => $profileInfo['location'],
                                      "friendTo" => $row['friendTo'],
                                      "friend" => $row['friend'],
                                      "createAt" => $row['createAt'],
                                      "date" => date("Y-m-d H:i:s", $row['createAt']),
                                      "timeAgo" => $profileInfo['lastAuthorizeTimeAgo'],
                                      "removeAt" => $row['removeAt']);

                    array_push($result['items'], $itemInfo);
                }

                unset($itemInfo);
            }
        }

        return $result;
    }

    public function getInboxRequests($itemId = 0, $limit = 20)
    {
        if ($itemId == 0) {

            $itemId = $this->getMaxIdFollowersTable();
            $itemId++;
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "itemId" => $itemId,
                        "items" => array());

        $stmt = $this->db->prepare("SELECT * FROM profile_followers WHERE follow_to = (:follow_to) AND id < (:itemId) ORDER BY id DESC LIMIT :limit");
        $stmt->bindParam(':follow_to', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $profile = new profile($this->db, $row['follower']);
                    $profile->setRequestFrom($this->requestFrom);

                    $profileItem = $profile->getVeryShort();

                    $profileItem['item_id'] = $row['id'];
                    $profileItem['create_at'] = $row['create_at'];

                    array_push($result['items'], $profileItem);

                    $result['itemId'] = $row['id'];

                    unset($profile);
                }
            }
        }

        return $result;
    }

    public function getOutboxRequests($itemId = 0)
    {
        if ($itemId == 0) {

            $itemId = $this->getMaxIdFollowersTable();
            $itemId++;
        }

        $result = array("error" => false,
                        "error_code" => ERROR_SUCCESS,
                        "itemId" => $itemId,
                        "items" => array());

        $stmt = $this->db->prepare("SELECT * FROM profile_followers WHERE follower = (:follower) AND id < (:itemId) AND follow_type = 0 ORDER BY id DESC LIMIT 20");
        $stmt->bindParam(':follower', $this->requestFrom, PDO::PARAM_INT);
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                while ($row = $stmt->fetch()) {

                    $profile = new profile($this->db, $row['follow_to']);
                    $profile->setRequestFrom($this->requestFrom);

                    $profileItem = $profile->getVeryShort();

                    $profileItem['item_id'] = $row['id'];
                    $profileItem['create_at'] = $row['create_at'];

                    array_push($result['items'], $profileItem);

                    $result['itemId'] = $row['id'];

                    unset($profile);
                }
            }
        }

        return $result;
    }

    public function getNewCount($lastFriendsView)
    {
        $stmt = $this->db->prepare("SELECT count(*) FROM friends WHERE friendTo = (:friendTo) AND createAt > (:lastFriendsView) AND removeAt = 0");
        $stmt->bindParam(":friendTo", $this->profileId, PDO::PARAM_INT);
        $stmt->bindParam(":lastFriendsView", $lastFriendsView, PDO::PARAM_INT);
        $stmt->execute();

        return $number_of_rows = $stmt->fetchColumn();
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
