<?php

/*!
     * ifsoft.co.uk v1.1
     *
     * http://ifsoft.com.ua, http://ifsoft.co.uk
     * raccoonsquare@gmail.com
     *
     * Copyright 2012-2018 Demyanchuk Dmitry (raccoonsquare@gmail.com)
     */

class community extends db_connect
{
    private $requestFrom = 0;

    public function __construct($dbo = NULL)
    {
        parent::__construct($dbo);
    }

    static function communityItem($community, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($community['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $community['lowPhotoUrl'];
        }

        ?>

        <li class="custom-list-item">

            <a href="/<?php echo $community['username']; ?>" class="item-logo" style="background-image:url(<?php echo $profilePhotoUrl; ?>)"></a>

            <a href="/<?php echo $community['username']; ?>" class="custom-item-link"><?php echo $community['fullname']; ?></a>

            <?php if ( $community['verify'] == 1) echo "<b original-title=\"{$LANG['label-account-verified']}\" class=\"verified\"></b>"; ?>

            <div class="item-meta">

                <span class="featured"><?php echo $LANG['page-followers']; ?>: <span class="username"><?php echo $community['followersCount']; ?></span></span>

            </div>

        </li>

        <?php
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

