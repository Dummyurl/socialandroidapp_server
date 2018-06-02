<?php

/*!
     * ifsoft.co.uk v1.1
     *
     * http://ifsoft.com.ua, http://ifsoft.co.uk
     * raccoonsquare@gmail.com
     *
     * Copyright 2012-2018 Demyanchuk Dmitry (raccoonsquare@gmail.com)
     */

class draw extends db_connect
{
    private $requestFrom = 0;

    public function __construct($dbo = NULL)
    {
        parent::__construct($dbo);
    }

    static function comment($comment, $postInfo, $LANG = array()) {

        $comment['comment'] = helper::processCommentText($comment['comment']);

        $fromUserPhoto = "/img/profile_default_photo.png";

        if (strlen($comment['fromUserPhotoUrl']) != 0) {

            $fromUserPhoto = $comment['fromUserPhotoUrl'];
        }

        ?>

        <li class="custom-list-item comment-item" data-id="<?php echo $comment['id']; ?>">

            <a href="/<?php echo $comment['fromUserUsername']; ?>" class="item-logo" style="background-image:url(<?php echo $fromUserPhoto; ?>)"></a>

            <?php if ($comment['fromUserOnline']) echo "<span title=\"Online\" class=\"item-logo-online\"></span>"; ?>

            <span class="custom-item-link"><?php echo $comment['comment']; ?></span>

            <div class="item-meta">

                <span class="featured"><?php echo $comment['timeAgo']; ?></span>

                <?php

                if ($comment['replyToUserId'] != 0) {

                    ?>
                    <span class="location"> <?php echo $LANG['label-to-user']; ?> <a href="/<?php echo $comment['replyToUserUsername']; ?>"><?php echo $comment['replyToFullname']; ?></a></span>
                    <?php
                }
                ?>

                <?php

                if ((auth::getCurrentUserId() != 0) && ($comment['fromUserId'] != auth::getCurrentUserId()) && ($postInfo['allowComments'] != 0) ) {

                    ?>
                    <span class="post-date"><a href="javascript:void(0)" onclick="Comments.reply('<?php echo $comment['fromUserId']; ?>', '<?php echo $comment['fromUserUsername']; ?>', '<?php echo $comment['fromUserFullname']; ?>'); return false;"><?php echo $LANG['action-reply']; ?></a></span>
                    <?php
                }
                ?>

                <?php

                if (auth::getCurrentUserId() != 0) {

                    if ($postInfo['fromUserId'] == auth::getCurrentUserId() || auth::getCurrentUserId() == $comment['fromUserId']) {

                        ?>

                        <span class="post-date"><a href="javascript:void(0)" onclick="Comments.remove('<?php echo $comment['fromUserUsername']; ?>', '<?php echo $postInfo['id']; ?>', '<?php echo $comment['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-remove']; ?></a></span>

                        <?php
                    }
                }

                ?>

            </div>

        </li>

        <?php
    }

    static function galleryItem($photo, $LANG, $helper)
    {

        ?>

        <div class="gallery-item" data-id="<?php echo $photo['id']; ?>">

            <div class="item-inner">

                <a href="/<?php echo $photo['fromUserUsername']; ?>/image/<?php echo $photo['id']; ?>">
                    <div class="gallery-item-preview" style="background-image:url(<?php echo $photo['previewImgUrl']; ?>)"></div>
                </a>

                <div class="gallery-item-titles">

                    <p class="gallery-item-author">

                        <span><?php echo $photo['timeAgo']; ?></span>

                        <?php

                        if (auth::getCurrentUserId() != 0 && auth::getCurrentUserId() == $photo['fromUserId']) {

                            ?>

                            | <span><a href="javascript:void(0)" onclick="Photo.remove('<?php echo $photo['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-remove']; ?></a></span>

                            <?php

                        } else {

                            ?>

                            | <span><a href="javascript:void(0)" onclick="Photo.getReportBox('<?php echo $photo['fromUserUsername']; ?>', '<?php echo $photo['id']; ?>', '<?php echo $LANG['action-report']; ?>'); return false;"><?php echo $LANG['action-report']; ?></a></span>

                            <?php
                        }
                        ?>
                    </p>

                </div>

            </div>
        </div>

        <?php
    }

    static function post($post, $LANG, $helper = null, $showComments = false)
    {
        $time = new language(NULL, $LANG['lang-code']);

        $post['post'] = helper::processPostText($post['post']);

        $fromUserPhoto = "/img/profile_default_photo.png";

        if (strlen($post['fromUserPhoto']) != 0) {

            $fromUserPhoto = $post['fromUserPhoto'];
        }

        if ($post['groupId'] != 0) {

            $group = new group(null, $post['groupId']);
            $group->setRequestFrom(auth::getCurrentUserId());

            $groupInfo = $group->get();

            if ($groupInfo['accountAuthor'] == $post['fromUserId']) {

                if (strlen($groupInfo['lowPhotoUrl'])) {

                    $fromUserPhoto = $groupInfo['lowPhotoUrl'];
                }
            }
        }

        ?>

        <li class="custom-list-item post-item" data-id="<?php echo $post['id']; ?>">

            <a href="/<?php echo $post['fromUserUsername']; ?>" class="item-logo" style="background-image:url(<?php echo $fromUserPhoto; ?>)"></a>

            <?php

                if ($post['groupId'] != 0 && isset($groupInfo)) {

                    if ($groupInfo['id'] != $post['fromUserId']) {

                        if ($post['fromUserOnline']) echo "<span title=\"Online\" class=\"item-logo-online\"></span>";
                    }

                } else {

                    if ($post['fromUserOnline']) echo "<span title=\"Online\" class=\"item-logo-online\"></span>";
                }

            ?>

            <a href="/<?php echo $post['fromUserUsername']; ?>" class="custom-item-link post-item-fullname"><?php echo $post['fromUserFullname']; ?></a>
            <?php if ( $post['fromUserVerify'] == 1) echo "<b original-title=\"{$LANG['label-account-verified']}\" class=\"verified\"></b>"; ?>

            <span class="post-item-time">
                <a href="/<?php echo $post['fromUserUsername']; ?>/post/<?php echo $post['id']; ?>"><?php echo $time->timeAgo($post['createAt']); ?></a>
                <?php if ( $post['deviceType'] == 1) echo "<b title=\"{$LANG['hint-item-android-version']}\" class=\"android-version-icon\"></b>"; ?>
                <?php if ( $post['deviceType'] == 2) echo "<b title=\"{$LANG['hint-item-ios-version']}\" class=\"ios-version-icon\"></b>"; ?>
            </span>

            <div class="item-meta post-item-content">

                <p class="post-text"><?php echo $post['post']; ?></p>

                <?php

                    if (strlen($post['imgUrl'])) {

                        ?>
                            <img class="post-img" style="" alt="post-img" src="<?php echo $post['imgUrl']; ?>">
                        <?php
                    }
                ?>

                <?php

                    if (strlen($post['videoUrl']) > 0) {

                        ?>

                        <video width = "100%" height = "auto" style="max-height: 300px" controls>
                            <source src="<?php echo $post['videoUrl']; ?>" type="video/mp4">
                        </video>

                        <?php
                    }
                ?>

                <?php

                    if (strlen($post['urlPreviewLink']) > 0) {

                        if (strlen($post['urlPreviewImage']) == 0) $post['urlPreviewImage'] = "/img/img_link.png";
                        if (strlen($post['urlPreviewTitle']) == 0) $post['urlPreviewTitle'] = "Link";

                        ?>

                        <ul class="items-list link-preview-list">
                            <li class="custom-list-item link-preview-item" data-id="<?php echo $post['id']; ?>">
                                <a href="<?php echo $post['urlPreviewLink']; ?>">
                                    <span class="link-img" style="background-image:url(<?php echo $post['urlPreviewImage']; ?>)"></span>
                                    <span class="link-title"><?php echo $post['urlPreviewTitle']; ?></span>

                                    <div class="item-meta">
                                        <span class="link-description"><?php echo $post['urlPreviewDescription']; ?></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                        <?php
                    }
                ?>

                <?php

                    $rePost = $post['rePost'];
                    $rePost = $rePost[0];

                    if ($post['rePostId'] != 0 && $rePost['error'] === false) {

                        if ($rePost['removeAt'] != 0) {

                            ?>

                            <div class="post post_item" data-id="<?php echo $rePost['id']; ?>" style="width: 100%;display: inline-block; border-left: 1px solid #DAE1E8; border-bottom: 0px; padding-left: 5px; margin-top: 10px; margin-bottom: 10px;">

                                <div class="post_content">
                                    <div class="post_data">
                                        <?php echo $LANG['label-repost-error']; ?>
                                    </div>
                                </div>
                            </div>

                            <?php

                        }  else {


                            $rePost['post'] = helper::processPostText($rePost['post']);

                            $rePostFromUserPhoto = "/img/profile_default_photo.png";

                            if (strlen($rePost['fromUserPhoto']) != 0) {

                                $rePostFromUserPhoto = $rePost['fromUserPhoto'];
                            }

                            ?>

                                <ul class="items-list repost-list">

                                    <li class="custom-list-item repost-item post-item" data-id="<?php echo $rePost['id']; ?>">

                                        <a href="/<?php echo $rePost['fromUserUsername']; ?>/post/<?php echo $rePost['id'] ?>" class="item-logo" style="background-image:url(<?php echo $rePostFromUserPhoto; ?>)"></a>

                                        <a href="/<?php echo $rePost['fromUserUsername']; ?>/post/<?php echo $rePost['id'] ?>" class="custom-item-link post-item-fullname"><?php echo $rePost['fromUserFullname']; ?></a>

                                        <span class="post-item-time"><?php echo $time->timeAgo($rePost['createAt']); ?></span>

                                        <div class="item-meta post-item-content">

                                            <p class="post-text"><?php echo $rePost['post']; ?></p>

                                            <?php

                                                if (strlen($rePost['imgUrl'])) {

                                                    ?>

                                                    <img class="post-img" style="" src="<?php echo $rePost['imgUrl']; ?>">
                                                    <?php
                                                }
                                            ?>

                                        </div
                                    </li>

                                </ul>

                            <?php
                        }
                    }

                ?>

                <div class="post-footer">

                    <?php

                    if ((auth::isSession() && $post['fromUserId'] == auth::getCurrentUserId()) || (isset($groupInfo) && $groupInfo['accountAuthor'] == auth::getCurrentUserId())) {

                        ?>

                        <span class="post-footer-link"><a href="javascript:void(0)" onclick="Post.remove('<?php echo $post['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-remove']; ?></a></span>

                        <?php

                    }

                    if (auth::isSession() && $post['fromUserId'] != auth::getCurrentUserId()) {

                        ?>

                        <span class="post-footer-link"><a href="javascript:void(0)" onclick="Post.getReportBox('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id']; ?>', '<?php echo $LANG['page-profile-report']; ?>'); return false;"><?php echo $LANG['action-report']; ?></a></span>

                        <?php
                    }

                    ?>

                    <?php

                        if (!$showComments) {

                            ?>

                                <span class="post-footer-link"><a href="/<?php echo $post['fromUserUsername']; ?>/post/<?php echo $post['id']; ?>"><?php echo $LANG['action-comment']; ?> <?php if ($post['commentsCount'] > 0) echo "({$post['commentsCount']})"; ?></a></span>

                            <?php

                            if (auth::isSession() && $post['fromUserId'] != auth::getCurrentUserId()) {

                                $re_post_id = $post['id'];

                                if ($post['rePostId'] != 0) {

                                    $re_post_id = $post['rePostId'];
                                }

                                ?>

                                <span class="post-footer-link"><a data-id="<?php echo $post['id']; ?>" onclick="Post.getRepostBox('<?php echo $post['fromUserUsername']; ?>', '<?php echo $re_post_id; ?>', '<?php echo $LANG['action-share-post']; ?>', '<?php echo $post['myRePost']; ?>'); return false;" class="post-share" href="javascript:void(0)"><?php echo $LANG['action-share']; ?> <?php if ($post['rePostsCount'] > 0) echo "({$post['rePostsCount']})"; ?></a></span>

                                <?php
                            }
                            ?>

                            <?php
                        }
                    ?>

                    <div class="likes-content" style="<?php if ($showComments) echo 'float: right;' ?>">
                        <span class="post-like" onclick="Post.like('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><i data-id="<?php echo $post['id']; ?>" class="like-icon <?php if ($post['myLike']) echo "mylike"; ?>"></i></span>
                        <span class="post-likes-count <?php if ($post['likesCount'] == 0) echo 'gone' ?> " data-id="<?php echo $post['id']; ?>"><a data-id="<?php echo $post['id']; ?>" class="likes-count" href="/<?php echo $post['fromUserUsername'].'/post/'.$post['id'].'/people'; ?>"><?php echo $post['likesCount']; ?></a></span>
                    </div>
                </div>

                <?php

                if ($showComments) {

                    ?>

                    <ul class="items-list comments-list" data-id="<?php echo $post['id']; ?>">

                        <?php

                        $comments = new comments();
                        $comments->setLanguage($LANG['lang-code']);
                        $comments->setRequestFrom(auth::getCurrentUserId());

                        $data = $comments->getPreview($post['id']);

                        $commentsCount = $data['count'];

                        if ($commentsCount > 3) {

                            ?>
                            <a data-id="<?php echo $post['id']; ?>" onclick="Comments.more('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id'] ?>', '<?php echo $data['commentId']; ?>'); return false;" class="get_comments_header comment-loader">
                                <?php echo $LANG['action-show-all']; ?> (<?php echo $commentsCount - 3; ?>)
                            </a>
                            <?php
                        }

                        $data['comments'] = array_reverse($data['comments'], false);

                        foreach ($data['comments'] as $key => $value) {

                            draw::comment($value, $post, $LANG);
                        }

                        ?>

                    </ul>

                    <?php

                        if (auth::getCurrentUserId() != 0) {

                            if ($post['allowComments'] != 0) {

                                ?>

                                <div class="comment_form comment-form" data-id="<?php echo $post['id']; ?>">

                                    <form class="" onsubmit="Comments.create('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id'] ?>', '<?php echo auth::getAccessToken(); ?>'); return false;">
                                        <input data-id="<?php echo $post['id']; ?>" class="comment_text" name="comment_text" maxlength="140" placeholder="<?Php echo $LANG['label-placeholder-comment']; ?>" type="text" value="">
                                        <button class="primary_btn comment_send blue"><?Php echo $LANG['action-send']; ?></button>
                                    </form>

                                </div>

                                <?php

                            } else {

                                ?>

                                <header class="top-banner info-banner" style="border: 0">

                                    <div class="info">
                                        <p style="white-space: normal; border: 0; text-align: center;"><?php echo $LANG['label-comments-disallow']; ?></p>
                                    </div>

                                </header>

                                <?php
                            }

                        } else {

                            ?>

                            <header class="top-banner info-banner" style="border: 0">

                                <div class="info">
                                    <p style="white-space: normal; border: 0; text-align: center;"><?php echo $LANG['label-comments-prompt']; ?></p>
                                </div>

                            </header>

                            <?php
                        }
                        ?>

                    <?php
                }
                ?>

            </div

        </li>

        <?php
    }

    static function userItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $profile['lowPhotoUrl'];
        }

        ?>

        <li class="custom-list-item">

            <a href="/<?php echo $profile['username']; ?>" class="item-logo" style="background-image:url(<?php echo $profilePhotoUrl; ?>)"></a>

            <a href="/<?php echo $profile['username']; ?>" class="custom-item-link"><?php echo $profile['fullname']; ?></a>

            <?php if ( $profile['verify'] == 1) echo "<b original-title=\"{$LANG['label-account-verified']}\" class=\"verified\"></b>"; ?>

            <div class="item-meta">

                <span class="featured">@<?php echo $profile['username']; ?></span>

            </div>

        </li>

        <?php
    }


    static function friendItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['friendUserPhoto']) != 0) {

            $profilePhotoUrl = $profile['friendUserPhoto'];
        }

        ?>

        <li class="card-item classic-item">
            <a href="/<?php echo $profile['friendUserUsername']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/>
                    <?php if ($profile['friendUserOnline']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title"><?php echo $profile['friendUserFullname']; ?>

                            <?php

                                if ($profile['friendUserVerify']) {

                                    ?>
                                        <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                    <?php
                                }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $profile['friendUserUsername']; ?></span>

                        <?php

                            if (strlen($profile['friendLocation']) > 0) {

                                ?>
                                    <span class="card-location"><?php echo $profile['friendLocation']; ?></span>
                                <?php
                            }

                            if ($profile['friendUserOnline']) {

                                ?>
                                    <span class="card-counter green">Online</span>
                                <?php

                            } else {

                                ?>
                                    <span title="<?php echo $LANG['label-last-seen']; ?>" class="card-counter black"><?php echo $profile['timeAgo']; ?></span>
                                <?php
                            }
                        ?>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function peopleItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $profile['lowPhotoUrl'];
        }

        ?>

        <li class="card-item classic-item">
            <a href="/<?php echo $profile['username']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/>
                    <?php if ($profile['online']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title"><?php echo $profile['fullname']; ?>

                            <?php

                            if ($profile['verify']) {

                                ?>
                                <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $profile['username']; ?></span>

                        <?php

                            if (strlen($profile['location']) > 0) {

                                ?>
                                    <span class="card-location"><?php echo $profile['location']; ?></span>
                                <?php
                            }

                            if ($profile['online']) {

                                ?>
                                    <span class="card-counter green">Online</span>
                                <?php

                            } else {

                                ?>
                                    <span title="<?php echo $LANG['label-last-seen']; ?>" class="card-counter black"><?php echo $profile['lastAuthorizeTimeAgo']; ?></span>
                                <?php
                            }
                        ?>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function nearbyItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $profile['lowPhotoUrl'];
        }

        ?>

        <li class="card-item classic-item">
            <a href="/<?php echo $profile['username']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/>
                    <?php if ($profile['online']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title"><?php echo $profile['fullname']; ?>

                            <?php

                            if ($profile['verify']) {

                                ?>
                                <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $profile['username']; ?></span>

                        <?php

                            if (strlen($profile['location']) > 0) {

                                ?>
                                    <span class="card-location"><?php echo $profile['location']; ?></span>
                                <?php
                            }
                        ?>

                        <span class="card-counter yellow"><?php echo $profile['distance']; ?>km</span>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function guestItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['guestUserPhoto']) != 0) {

            $profilePhotoUrl = $profile['guestUserPhoto'];
        }

        ?>

        <li class="card-item classic-item">
            <a href="/<?php echo $profile['guestUserUsername']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/>
                    <?php if ($profile['guestUserOnline']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title"><?php echo $profile['guestUserFullname']; ?>

                            <?php

                            if ($profile['guestUserVerify']) {

                                ?>
                                <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $profile['guestUserUsername']; ?></span>

                        <span title="<?php echo $LANG['label-last-visit']; ?>" class="card-counter black"><?php echo $profile['timeAgo']; ?></span>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function blackListItem($profile, $LANG, $helper = null)
    {
        ?>

        <li class="card-item classic-item" data-id="<?php echo $profile['id']; ?>">
            <a href="/<?php echo $profile['blockedUserUsername']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $profile['blockedUserPhotoUrl']; ?>"/>
                    <div class="card-content">
                        <span class="card-title"><?php echo $profile['blockedUserFullname']; ?>

                            <?php

                            if ($profile['blockedUserVerify']) {

                                ?>
                                    <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $profile['blockedUserUsername']; ?></span>

                        <?php

                        if ($profile['blockedUserOnline']) {

                            ?>
                                <span class="card-date">Online</span>
                            <?php
                        }
                        ?>

                        <span class="card-action">
                            <span class="card-act negative" onclick="BlackList.remove('<?php echo $profile['id']; ?>', '<?php echo $profile['blockedUserUsername']; ?>', '<?php echo auth::getAuthenticityToken(); ?>'); return false;"><?php echo $LANG['action-unblock']; ?></span>
                        </span>

                        <span class="card-counter blue"><?php echo $profile['timeAgo']; ?></span>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function outboxFriendRequestItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $profile['lowPhotoUrl'];
        }

        $time = new language(NULL, $LANG['lang-code']);

        ?>

        <li class="card-item classic-item default-item" data-id="<?php echo $profile['id']; ?>">
            <div class="card-body">
                <span class="card-header">
                    <a href="/<?php echo $profile['username']; ?>"><img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/></a>
                    <?php if ($profile['online']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title">
                            <a href="/<?php echo $profile['username']; ?>"><?php echo  $profile['fullname']; ?></a>
                            <?php

                                if ($profile['verify'] == 1) {

                                    ?>
                                        <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                    <?php
                                }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo  $profile['username']; ?></span>
                        <span class="card-counter black" title="<?php echo $LANG['label-create-at']; ?>"><?php echo $time->timeAgo($profile['create_at']);  ?></span>
                        <span class="card-action">
                            <a class="card-act negative" href="javascript:void(0)" onclick="Friends.cancelRequest('<?php echo $profile['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-cancel']; ?></a>
                        </span>
                    </div>
                </span>
            </div>
        </li>

        <?php
    }

    static function inboxFriendRequestItem($profile, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($profile['lowPhotoUrl']) != 0) {

            $profilePhotoUrl = $profile['lowPhotoUrl'];
        }

        $time = new language(NULL, $LANG['lang-code']);

        ?>

        <li class="card-item classic-item default-item" data-id="<?php echo $profile['id']; ?>">
            <div class="card-body">
                <span class="card-header">
                    <a href="/<?php echo $profile['username']; ?>"><img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/></a>
                    <?php if ($profile['online']) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">
                        <span class="card-title">
                            <a href="/<?php echo $profile['username']; ?>"><?php echo  $profile['fullname']; ?></a>
                            <?php

                                if ($profile['verify'] == 1) {

                                    ?>
                                        <b original-title="<?php echo $LANG['label-account-verified']; ?>" class="verified"></b>
                                    <?php
                                }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo  $profile['username']; ?></span>
                        <span class="card-counter black" title="<?php echo $LANG['label-create-at']; ?>"><?php echo $time->timeAgo($profile['create_at']);  ?></span>
                        <span class="card-action">
                            <a class="card-act negative" href="javascript:void(0)" onclick="Friends.rejectRequest('<?php echo $profile['id']; ?>', '<?php echo $profile['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-reject']; ?></a>
                            <a class="card-act active" href="javascript:void(0)" onclick="Friends.acceptRequest('<?php echo $profile['id']; ?>', '<?php echo $profile['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-accept']; ?></a>
                        </span>
                    </div>
                </span>
            </div>
        </li>

        <?php
    }

    static function communityItem($item, $LANG, $helper = null)
    {
        $itemPhotoUrl = "/img/profile_default_photo.png";

        if (strlen($item['lowPhotoUrl']) != 0) {

            $itemPhotoUrl = $item['lowPhotoUrl'];
        }

        ?>

        <li class="card-item classic-item">
            <a href="/<?php echo $item['username']; ?>" class="card-body">
                <span class="card-header">
                    <img class="card-icon" src="<?php echo $itemPhotoUrl; ?>"/>
                    <div class="card-content">
                        <span class="card-title"><?php echo $item['fullname']; ?>

                            <?php

                            if ($item['verify']) {

                                ?>
                                    <b original-title="<?php echo $LANG['label-community-verified']; ?>" class="verified"></b>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="card-username">@<?php echo $item['username']; ?></span>

                        <?php

                            if (strlen($item['location']) > 0) {

                                ?>
                                    <span class="card-location"><?php echo $item['location']; ?></span>
                                <?php
                            }

                            if (strlen($item['status']) > 0) {

                                ?>
                                    <span class="card-status-text"><?php echo $item['status']; ?></span>
                                <?php
                            }
                        ?>

                        <span class="card-counter blue"><?php echo $item['followersCount']." ".$LANG['label-community-followers']; ?></span>
                    </div>
                </span>
            </a>
        </li>

        <?php
    }

    static function messageItem($message, $LANG, $helper = null)
    {
        $profilePhotoUrl = "/img/profile_default_photo.png";

        if (strlen($message['fromUserPhotoUrl']) != 0) {

            $profilePhotoUrl = $message['fromUserPhotoUrl'];
        }

        $time = new language(NULL, $LANG['lang-code']);

        $seen = false;

        if ($message['fromUserId'] == auth::getCurrentUserId() && $message['seenAt'] != 0 ) {

            $seen = true;
        }

        ?>

        <li class="card-item default-item message-item <?php if ($message['fromUserId'] == auth::getCurrentUserId()) echo "message-item-right"; ?>" data-id="<?php echo $message['id']; ?>">
            <div class="card-body">
                <span class="card-header">
                    <a href="/<?php echo $message['fromUserUsername']; ?>"><img class="card-icon" src="<?php echo $profilePhotoUrl; ?>"/></a>
                    <?php if ($message['fromUserOnline'] && $message['fromUserId'] != auth::getCurrentUserId()) echo "<span title=\"Online\" class=\"card-online-icon\"></span>"; ?>
                    <div class="card-content">

                        <?php

                        if ($message['stickerId'] != 0) {

                            ?>
                                <img class="sticker-img" style="" alt="sticker-img" src="<?php echo $message['stickerImgUrl']; ?>">
                            <?php

                        } else {

                            ?>
                            <span class="card-status-text">

                                    <?php

                                    if (strlen($message['message']) > 0) {

                                        ?>
                                            <span class="card-status-text-message">
                                                <?php echo $message['message']; ?>
                                            </span>
                                        <?php
                                    }

                                    if (strlen($message['imgUrl']) > 0) {

                                        ?>
                                            <img class="post-img" style="" alt="post-img" src="<?php echo $message['imgUrl']; ?>">
                                        <?php
                                    }

                                    ?>

                                    </span>
                            <?php
                        }
                        ?>

                        <span class="card-date">
                            <?php echo $time->timeAgo($message['createAt']); ?>
                            <span class="time green" style="<?php if (!$seen) echo 'display: none'; ?>" data-my-id="<?php echo $LANG['label-seen']; ?>"><?php echo $LANG['label-seen']; ?></span>
                        </span>

                    </div>
                </span>
            </div>
        </li>

        <?php
    }

    static function image($post, $LANG, $helper = null, $showComments = false)
    {
        $post['comment'] = helper::processPostText($post['comment']);

        $fromUserPhoto = "/img/profile_default_photo.png";

        if (strlen($post['fromUserPhoto']) != 0) {

            $fromUserPhoto = $post['fromUserPhoto'];
        }

        $time = new language(NULL, $LANG['lang-code']);

        ?>

        <li class="custom-list-item post-item" data-id="<?php echo $post['id']; ?>">

            <a href="/<?php echo $post['fromUserUsername']; ?>" class="item-logo" style="background-image:url(<?php echo $fromUserPhoto; ?>)"></a>

            <a href="/<?php echo $post['fromUserUsername']; ?>" class="custom-item-link post-item-fullname"><?php echo $post['fromUserFullname']; ?></a>

            <span class="post-item-time"><a href="/<?php echo $post['fromUserUsername']; ?>/image/<?php echo $post['id']; ?>"><?php echo $time->timeAgo($post['createAt']); ?></a></span>

            <div class="item-meta post-item-content">

                <p class="post-text"><?php echo $post['comment']; ?></p>

                <?php

                if (strlen($post['imgUrl'])) {

                    ?>
                    <img class="post-img" style="" alt="post-img" src="<?php echo $post['imgUrl']; ?>">
                    <?php
                }
                ?>

                <div class="post-footer">

                    <?php

                    if (auth::isSession() && $post['fromUserId'] == auth::getCurrentUserId()) {

                        ?>

                        <span class="post-footer-link"><a href="javascript:void(0)" onclick="Images.remove('<?php echo $post['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-remove']; ?></a></span>

                        <?php

                    }

                    if (auth::isSession() && $post['fromUserId'] != auth::getCurrentUserId()) {

                        ?>

                        <span class="post-footer-link"><a href="javascript:void(0)" onclick="Photo.getReportBox('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id']; ?>', '<?php echo $LANG['action-report']; ?>'); return false;"><?php echo $LANG['action-report']; ?></a></span>

                        <?php
                    }

                    ?>

                    <?php

                    if (!$showComments) {

                        ?>

                        <span class="post-footer-link"><a href="/<?php echo $post['fromUserUsername']; ?>/post/<?php echo $post['id']; ?>"><?php echo $LANG['action-comment']; ?> <?php if ($post['commentsCount'] > 0) echo "({$post['commentsCount']})"; ?></a></span>

                        <?php

                        if (auth::isSession() && $post['fromUserId'] != auth::getCurrentUserId()) {

                            $re_post_id = $post['id'];

                            if ($post['rePostId'] != 0) {

                                $re_post_id = $post['rePostId'];
                            }

                            ?>

                            <span class="post-footer-link"><a data-id="<?php echo $post['id']; ?>" onclick="Post.getRepostBox('<?php echo $post['fromUserUsername']; ?>', '<?php echo $re_post_id; ?>', '<?php echo $LANG['action-share-post']; ?>', '<?php echo $post['myRePost']; ?>'); return false;" class="post-share" href="javascript:void(0)"><?php echo $LANG['action-share']; ?> <?php if ($post['rePostsCount'] > 0) echo "({$post['rePostsCount']})"; ?></a></span>

                            <?php
                        }
                        ?>

                        <?php
                    }
                    ?>

                    <span class="post-like" onclick="Images.like('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><i data-id="<?php echo $post['id']; ?>" class="like-icon <?php if ($post['myLike']) echo "mylike"; ?>"></i></span>
                    <span class="post-likes-count <?php if ($post['likesCount'] == 0) echo 'gone' ?> " data-id="<?php echo $post['id']; ?>"><a data-id="<?php echo $post['id']; ?>" class="likes-count" href="/<?php echo $post['fromUserUsername'].'/image/'.$post['id'].'/people'; ?>"><?php echo $post['likesCount']; ?></a></span>
                </div>

                <?php

                if ($showComments) {

                    ?>

                    <ul class="items-list comments-list" data-id="<?php echo $post['id']; ?>">

                        <?php

                        $images = new images();
                        $images->setLanguage($LANG['lang-code']);
                        $images->setRequestFrom(auth::getCurrentUserId());

                        $data = $images->commentsGet($post['id']);

                        $commentsCount = count($data['comments']);

                        $data['comments'] = array_reverse($data['comments'], false);

                        foreach ($data['comments'] as $key => $value) {

                            draw::image_comment($value, $post, $LANG);
                        }

                        ?>

                    </ul>

                    <?php

                    if (auth::getCurrentUserId() != 0) {

                        if ($post['fromUserAllowPhotosComments'] != 0) {

                            ?>

                            <div class="comment_form comment-form" data-id="<?php echo $post['id']; ?>">

                                <form class="" onsubmit="imagesComments.create('<?php echo $post['fromUserUsername']; ?>', '<?php echo $post['id'] ?>', '<?php echo auth::getAccessToken(); ?>'); return false;">
                                    <input data-id="<?php echo $post['id']; ?>" class="comment_text" name="comment_text" maxlength="140" placeholder="<?Php echo $LANG['label-placeholder-comment']; ?>" type="text" value="">
                                    <button class="primary_btn comment_send blue"><?Php echo $LANG['action-send']; ?></button>
                                </form>

                            </div>

                            <?php

                        } else {

                            ?>

                            <header class="top-banner info-banner" style="border: 0">

                                <div class="info">
                                    <p style="white-space: normal; border: 0; text-align: center;"><?php echo $LANG['label-comments-disallow']; ?></p>
                                </div>

                            </header>

                            <?php
                        }

                    } else {

                        ?>

                        <header class="top-banner info-banner" style="border: 0">

                            <div class="info">
                                <p style="white-space: normal; border: 0; text-align: center;"><?php echo $LANG['label-comments-prompt']; ?></p>
                            </div>

                        </header>

                        <?php
                    }
                    ?>

                    <?php
                }
                ?>

            </div

        </li>

        <?php
    }

    static function image_comment($comment, $postInfo, $LANG = array()) {

        $comment['comment'] = helper::processCommentText($comment['comment']);

        $fromUserPhoto = "/img/profile_default_photo.png";

        if (strlen($comment['fromUserPhotoUrl']) != 0) {

            $fromUserPhoto = $comment['fromUserPhotoUrl'];
        }

        ?>

        <li class="custom-list-item comment-item" data-id="<?php echo $comment['id']; ?>">

            <a href="/<?php echo $comment['fromUserUsername']; ?>" class="item-logo" style="background-image:url(<?php echo $fromUserPhoto; ?>)"></a>

            <span class="custom-item-link"><?php echo $comment['comment']; ?></span>

            <div class="item-meta">

                <span class="featured"><?php echo $comment['timeAgo']; ?></span>

                <?php

                if ($comment['replyToUserId'] != 0) {

                    ?>
                    <span class="location"> <?php echo $LANG['label-to-user']; ?> <a href="/<?php echo $comment['replyToUserUsername']; ?>"><?php echo $comment['replyToFullname']; ?></a></span>
                    <?php
                }
                ?>

                <?php

                if ((auth::getCurrentUserId() != 0) && ($comment['fromUserId'] != auth::getCurrentUserId()) && ($postInfo['fromUserAllowPhotosComments'] != 0) ) {

                    ?>
                    <span class="post-date"><a href="javascript:void(0)" onclick="imagesComments.reply('<?php echo $comment['fromUserId']; ?>', '<?php echo $comment['fromUserUsername']; ?>', '<?php echo $comment['fromUserFullname']; ?>'); return false;"><?php echo $LANG['action-reply']; ?></a></span>
                    <?php
                }
                ?>

                <?php

                if (auth::getCurrentUserId() != 0) {

                    if ($postInfo['fromUserId'] == auth::getCurrentUserId() || auth::getCurrentUserId() == $comment['fromUserId']) {

                        ?>

                        <span class="post-date"><a href="javascript:void(0)" onclick="imagesComments.remove('<?php echo $comment['fromUserUsername']; ?>', '<?php echo $postInfo['id']; ?>', '<?php echo $comment['id']; ?>', '<?php echo auth::getAccessToken(); ?>'); return false;"><?php echo $LANG['action-remove']; ?></a></span>

                        <?php
                    }
                }

                ?>

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

