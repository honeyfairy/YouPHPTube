<?php
global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
session_write_close();
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/category.php';
require_once $global['systemRootPath'] . 'objects/subscribe.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

$img = "{$global['webSiteRootURL']}view/img/notfound.jpg";
$poster = "{$global['webSiteRootURL']}view/img/notfound.jpg";
$imgw = 1280;
$imgh = 720;

if (!empty($_GET['type'])) {
    if ($_GET['type'] == 'audio') {
        $_SESSION['type'] = 'audio';
    } else
    if ($_GET['type'] == 'video') {
        $_SESSION['type'] = 'video';
    } else {
        $_SESSION['type'] = "";
        unset($_SESSION['type']);
    }
} else {
    unset($_SESSION['type']);
}
require_once $global['systemRootPath'] . 'objects/video.php';

$catLink = "";
if (!empty($_GET['catName'])) {
    $catLink = "cat/{$_GET['catName']}/";
}

// add this because if you change the video category the video was not loading anymore
$catName = @$_GET['catName'];

if (empty($_GET['clean_title'])) {
    $_GET['catName'] = "";
}

$video = Video::getVideo("", "viewable", false, false, true, true);

if (empty($video)) {
    $video = Video::getVideo("", "viewable", false, false, false, true);
}
// add this because if you change the video category the video was not loading anymore
$_GET['catName'] = $catName;

$_GET['isMediaPlaySite'] = $video['id'];
$obj = new Video("", "", $video['id']);

if (empty($_SESSION['type'])) {
    $_SESSION['type'] = $video['type'];
}
// $resp = $obj->addView();

if (!empty($_GET['playlist_id'])) {
    $playlist_id = $_GET['playlist_id'];
    if (!empty($_GET['playlist_index'])) {
        $playlist_index = $_GET['playlist_index'];
    } else {
        $playlist_index = 0;
    }

    $videosArrayId = PlayList::getVideosIdFromPlaylist($_GET['playlist_id']);
    $videosPlayList = Video::getAllVideos("viewable");
    $videosPlayList = PlayList::sortVideos($videosPlayList, $videosArrayId);
    $video = Video::getVideo($videosPlayList[$playlist_index]['id']);
    if (!empty($videosPlayList[$playlist_index + 1])) {
        $autoPlayVideo = Video::getVideo($videosPlayList[$playlist_index + 1]['id']);
        $autoPlayVideo['url'] = $global['webSiteRootURL'] . "playlist/{$playlist_id}/" . ($playlist_index + 1);
    }else if (!empty($videosPlayList[0])) {
        $autoPlayVideo = Video::getVideo($videosPlayList[0]['id']);
        $autoPlayVideo['url'] = $global['webSiteRootURL'] . "playlist/{$playlist_id}/0";
    }

    unset($_GET['playlist_id']);
} else {
    if (!empty($video['next_videos_id'])) {
        $autoPlayVideo = Video::getVideo($video['next_videos_id']);
    } else {
        if ($video['category_order'] == 1) {
            unset($_POST['sort']);
            $category = Category::getAllCategories();
            $_POST['sort']['title'] = "ASC";

            // maybe there's a more slim method?
            $videos = Video::getAllVideos();
            $videoFound = false;
            $autoPlayVideo;
            foreach ($videos as $value) {
                if ($videoFound) {
                    $autoPlayVideo = $value;
                    break;
                }

                if ($value['id'] == $video['id']) {
                    // if the video is found, make another round to have the next video properly.
                    $videoFound = true;
                }
            }
        } else {
            $autoPlayVideo = Video::getRandom($video['id']);
        }
    }

    if (!empty($autoPlayVideo)) {
        $name2 = User::getNameIdentificationById($autoPlayVideo['users_id']);
        $autoPlayVideo['creator'] = '<div class="float-left"><img src="' . User::getPhoto($autoPlayVideo['users_id']) . '" alt="" class="img img-fluid rounded-circle zoom" style="max-width: 40px;"/></div><div class="commentDetails" style="margin-left:45px;"><div class="commenterName"><strong>' . $name2 . '</strong> <small>' . humanTiming(strtotime($autoPlayVideo['videoCreation'])) . '</small></div></div>';
        $autoPlayVideo['tags'] = Video::getTags($autoPlayVideo['id']);
        $autoPlayVideo['url'] = $global['webSiteRootURL'] . $catLink . "video/" . $autoPlayVideo['clean_title'];
    }
}

if (!empty($video)) {
    $name = User::getNameIdentificationById($video['users_id']);
    $name = "<a href='" . User::getChannelLink($video['users_id']) . "' class='btn-sm btn-light'>{$name}</a>";
    $subscribe = Subscribe::getButton($video['users_id']);
    $video['creator'] = '<div class="float-left"><img src="' . User::getPhoto($video['users_id']) . '" alt="" class="img img-fluid rounded-circle zoom" style="max-width: 40px;"/></div><div class="commentDetails" style="margin-left:45px;"><div class="commenterName text-muted"><strong>' . $name . '</strong><br />' . $subscribe . '<br /><small>' . humanTiming(strtotime($video['videoCreation'])) . '</small></div></div>';
    $obj = new Video("", "", $video['id']);

    // dont need because have one embeded video on this page
    // $resp = $obj->addView();
}

if ($video['type'] == "video") {
    $poster = "{$global['webSiteRootURL']}videos/{$video['filename']}.jpg";
} else {
    $poster = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
}

if (!empty($video)) {
    if (($video['type'] !== "audio") && ($video['type'] !== "linkAudio")) {
        $source = Video::getSourceFile($video['filename']);
        $img = $source['url'];
        $data = getimgsize($source['path']);
        $imgw = $data[0];
        $imgh = $data[1];
    } else {
        $img = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
    }
    $images = Video::getImageFromFilename($video['filename']);
    $poster = $images->poster;
} else {
    $poster = "{$global['webSiteRootURL']}view/img/notfound.jpg";
}

$objSecure = YouPHPTubePlugin::getObjectDataIfEnabled('SecureVideosDirectory');
$advancedCustom = YouPHPTubePlugin::getObjectDataIfEnabled("CustomizeAdvanced");

if(!empty($autoPlayVideo)){
    $autoPlaySources = getSources($autoPlayVideo['filename'], true);
    $autoPlayURL = $autoPlayVideo['url'];
    $autoPlayPoster = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}.jpg";
    $autoPlayThumbsSprit = "{$global['webSiteRootURL']}videos/{$autoPlayVideo['filename']}_thumbsSprit.jpg";
}else{
    $autoPlaySources = array();
    $autoPlayURL = '';
    $autoPlayPoster = '';
    $autoPlayThumbsSprit = "";
}

if (empty($_GET['videoName'])) {
    $_GET['videoName'] = $video['clean_title'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $video['title']; ?> - <?php echo $config->getWebSiteTitle(); ?></title>
        <?php include $global['systemRootPath'] . 'view/include/head.php'; ?>
        <link rel="image_src" href="<?php echo $img; ?>" />
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/video.js/video-js.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/player.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/social.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
        <meta property="fb:app_id"             content="774958212660408" />
        <meta property="og:url"                content="<?php echo $global['webSiteRootURL'], $catLink, "video/", $video['clean_title']; ?>" />
        <meta property="og:type"               content="video.other" />
        <meta property="og:title"              content="<?php echo str_replace('"', '', $video['title']); ?> - <?php echo $config->getWebSiteTitle(); ?>" />
        <meta property="og:description"        content="<?php echo str_replace('"', '', $video['title']); ?>" />
        <meta property="og:image"              content="<?php echo $img; ?>" />
        <meta property="og:image:width"        content="<?php echo $imgw; ?>" />
        <meta property="og:image:height"       content="<?php echo $imgh; ?>" />
        <meta property="video:duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
        <meta property="duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
    </head>

    <body>
        <?php include $global['systemRootPath'] . 'view/include/navbar.php'; ?>
        <div class="container-fluid principalContainer" itemscope itemtype="http://schema.org/VideoObject">
            <?php
            if (!empty($video)) {
                if (empty($video['type'])) {
                    $video['type'] = "video";
                }
                $img_portrait = ($video['rotation'] === "90" || $video['rotation'] === "270") ? "img-portrait" : "";
                if (!empty($advancedCustom->showAdsenseBannerOnTop)) {
                    ?>
                    <style>
                        .compress {
                            top: 100px !important;
                        }
                    </style>
                    <div class="row">
                        <div class="col-lg-12 col-sm-12 col-12">
                            <center style="margin:5px;">
                                <?php
                                echo $config->getAdsense();
                                ?>
                            </center>
                        </div>
                    </div>
                    <?php
                }
                $vType = $video['type'];
                if ($vType == "linkVideo") {
                    $vType = "video";
                } else if ($vType == "linkAudio") {
                    $vType = "audio";
                }
                require "{$global['systemRootPath']}view/include/{$vType}.php";
                ?>
                <div class="row" id="modeYoutubeBottom">
                    <div class="col-sm-1 col-md-1"></div>
                    <div class="col-sm-6 col-md-6">
                        <div class="row bgWhite border border-light rounded bg-light">
                            <div class="row divMainVideo">
                                <div class="col-4 col-sm-4 col-md-4">
                                    <img src="<?php echo $poster; ?>" alt="<?php echo str_replace('"', '', $video['title']); ?>" class="img img-fluid <?php echo $img_portrait; ?> rotate<?php echo $video['rotation']; ?>" height="130" itemprop="thumbnail" />
                                    <time class="duration" itemprop="duration" datetime="<?php echo Video::getItemPropDuration($video['duration']); ?>" ><?php echo Video::getCleanDuration($video['duration']); ?></time>
                                    <meta itemprop="thumbnailUrl" content="<?php echo $img; ?>" />
                                    <meta itemprop="contentURL" content="<?php echo Video::getLink($video['id'], $video['clean_title']); ?>" />
                                    <meta itemprop="embedURL" content="<?php echo Video::getLink($video['id'], $video['clean_title'], true); ?>" />
                                    <meta itemprop="uploadDate" content="<?php echo $video['created']; ?>" />
                                    <meta itemprop="description" content="<?php echo str_replace('"', '', $video['title']); ?> - <?php echo htmlentities($video['description']); ?>" />
                                </div>
                                <div class="col-8 col-sm-8 col-md-8">
                                    <h1 itemprop="name">
                                        <?php
                                        echo $video['title'];
                                        if (Video::canEdit($video['id'])) {
                                            ?>
                                            <a href="<?php echo $global['webSiteRootURL']; ?>mvideos?video_id=<?php echo $video['id']; ?>" class="btn-link btn-sm btn" data-toggle="tooltip" title="<?php echo __("Edit Video"); ?>"><i class="fa fa-edit"></i> <?php echo __("Edit Video"); ?></a>
                                        <?php } ?>
                                        <small>
                                            <?php
                                            if (!empty($video['id'])) {
                                                $video['tags'] = Video::getTags($video['id']);
                                            } else {
                                                $video['tags'] = array();
                                            }
                                            foreach ($video['tags'] as $value) {
                                                if ($value->label === __("Group")) {
                                                    ?>
                                                    <span class="badge badge-<?php echo $value->type; ?>"><?php echo $value->text; ?></span>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </small>
                                    </h1>
                                    <div class="col-12 col-sm-12 col-md-12">
                                        <?php echo $video['creator']; ?>
                                    </div>
                                    <span class="watch-view-count float-right text-muted" itemprop="interactionCount"><span class="view-count<?php echo $video['id']; ?>"><?php echo number_format($video['views_count'], 0); ?></span> <?php echo __("Views"); ?></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 watch8-action-buttons text-muted">
                                    <?php if ((($advancedCustom != false) && ($advancedCustom->disableShareAndPlaylist == false)) || ($advancedCustom == false)) { ?>
                                        <button class="btn btn-light" id="addBtn" data-placement="bottom">
                                            <span class="fa fa-plus"></span> <?php echo __("Add to"); ?>
                                        </button>
                                        <div class="webui-popover-content">
                                            <?php if (User::isLogged()) { ?>
                                                <form role="form">
                                                    <div class=" ">
                                                        <input class="form-control" id="searchinput" type="search" placeholder="Search..." />
                                                    </div>
                                                    <div id="searchlist" class="list-group">
                                                    </div>
                                                </form>
                                                <div>
                                                    <hr>
                                                    <div class=" ">
                                                        <input id="playListName" class="form-control" placeholder="<?php echo __("Create a New Play List"); ?>"  >
                                                    </div>
                                                    <div class=" ">
                                                        <?php echo __("Make it public"); ?>
                                                        <div class="material-switch float-right">
                                                            <input id="publicPlayList" name="publicPlayList" type="checkbox" checked="checked"/>
                                                            <label for="publicPlayList" class="badge-success"></label>
                                                        </div>
                                                    </div>
                                                    <div class=" ">
                                                        <button class="btn btn-success btn-block" id="addPlayList" ><?php echo __("Create a New Play List"); ?></button>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                              <h5><?php echo __("Want to watch this again later?"); ?></h5>
                                                <?php echo __("Sign in to add this video to a playlist."); ?>
                                                <a href="<?php echo $global['webSiteRootURL']; ?>user" class="btn btn-primary">
                                                    <span class="fas fa-sign-in-alt"></span>
                                                    <?php echo __("Login"); ?>
                                                </a>
                                            <?php } ?>
                                        </div>
                                        <script>
                                            function loadPlayLists() {
                                                $.ajax({
                                                    url: '<?php echo $global['webSiteRootURL']; ?>objects/playlists.json.php',
                                                    success: function (response) {
                                                        $('#searchlist').html('');
                                                        for (var i in response) {
                                                            if (!response[i].id) {
                                                                continue;
                                                            }
                                                            var icon = "lock"
                                                            if (response[i].status == "public") {
                                                                icon = "globe"
                                                            }

                                                            var checked = "";
                                                            for (var x in response[i].videos) {
                                                                if (
                                                                        typeof (response[i].videos[x]) === 'object'
                                                                        && response[i].videos[x].videos_id ==<?php echo $video['id']; ?>) {
                                                                    checked = "checked";
                                                                }
                                                            }

                                                            $("#searchlist").append('<a class="bg-light"><i class="fa fa-' + icon + '"></i> <span>'
                                                                    + response[i].name + '</span><div class="material-switch float-right"><input id="someSwitchOptionDefault'
                                                                    + response[i].id + '" name="someSwitchOption' + response[i].id + '" class="playListsIds" type="checkbox" value="'
                                                                    + response[i].id + '" ' + checked + '/><label for="someSwitchOptionDefault'
                                                                    + response[i].id + '" class="badge-success"></label></div></a>');
                                                        }
                                                        $('#searchlist').btsListFilter('#searchinput', {itemChild: 'span'});
                                                        $('.playListsIds').change(function () {
                                                            modal.showPleaseWait();
                                                            $.ajax({
                                                                url: '<?php echo $global['webSiteRootURL']; ?>objects/playListAddVideo.json.php',
                                                                method: 'POST',
                                                                data: {
                                                                    'videos_id': <?php echo $video['id']; ?>,
                                                                    'add': $(this).is(":checked"),
                                                                    'playlists_id': $(this).val()
                                                                },
                                                                success: function (response) {
                                                                    modal.hidePleaseWait();
                                                                }
                                                            });
                                                            return false;
                                                        });
                                                    }
                                                });
                                            }
                                            $(document).ready(function () {
                                                loadPlayLists();
                                                $('#addBtn').webuiPopover();
                                                $('#addPlayList').click(function () {
                                                    modal.showPleaseWait();
                                                    $.ajax({
                                                        url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistAddNew.json.php',
                                                        method: 'POST',
                                                        data: {
                                                            'videos_id': <?php echo $video['id']; ?>,
                                                            'status': $('#publicPlayList').is(":checked") ? "public" : "private",
                                                            'name': $('#playListName').val()
                                                        },
                                                        success: function (response) {
                                                            if (response.status * 1 > 0) {
                                                                // update list
                                                                loadPlayLists();
                                                                $('#searchlist').btsListFilter('#searchinput', {itemChild: 'span'});
                                                                $('#playListName').val("");
                                                                $('#publicPlayList').prop('checked', true);
                                                            }
                                                            modal.hidePleaseWait();
                                                        }
                                                    });
                                                    return false;
                                                });

                                            });
                                        </script>
                                        <a href="#" class="btn btn-light" id="shareBtn">
                                            <span class="fa fa-share"></span> <?php echo __("Share"); ?>
                                        </a>
                                    <?php } echo YouPHPTubePlugin::getWatchActionButton(); ?>
                                    <a href="#" class="btn btn-light float-right <?php echo ($video['myVote'] == - 1) ? "myVote" : "" ?>" id="dislikeBtn" <?php if (!User::isLogged()) { ?> data-toggle="tooltip" title="<?php echo __("DonÂ´t like this video? Sign in to make your opinion count."); ?>" <?php } ?>>
                                        <span class="fa fa-thumbs-down"></span> <small><?php echo $video['dislikes']; ?></small>
                                    </a>
                                    <a href="#" class="btn btn-light float-right <?php echo ($video['myVote'] == 1) ? "myVote" : "" ?>" id="likeBtn" <?php if (!User::isLogged()) { ?> data-toggle="tooltip" title="<?php echo __("Like this video? Sign in to make your opinion count."); ?>" <?php } ?>>
                                        <span class="fa fa-thumbs-up"></span>
                                        <small><?php echo $video['likes']; ?></small>
                                    </a>
                                    <script>
                                        $(document).ready(function () {
    <?php if (User::isLogged()) { ?>
                                                $("#dislikeBtn, #likeBtn").click(function () {
                                                    $.ajax({
                                                        url: '<?php echo $global['webSiteRootURL']; ?>' + ($(this).attr("id") == "dislikeBtn" ? "dislike" : "like"),
                                                        method: 'POST',
                                                        data: {'videos_id': <?php echo $video['id']; ?>},
                                                        success: function (response) {
                                                            $("#likeBtn, #dislikeBtn").removeClass("myVote");
                                                            if (response.myVote == 1) {
                                                                $("#likeBtn").addClass("myVote");
                                                            } else if (response.myVote == -1) {
                                                                $("#dislikeBtn").addClass("myVote");
                                                            }
                                                            $("#likeBtn small").text(response.likes);
                                                            $("#dislikeBtn small").text(response.dislikes);
                                                        }
                                                    });
                                                    return false;
                                                });
    <?php } else { ?>
                                                $("#dislikeBtn, #likeBtn").click(function () {
                                                    $(this).tooltip("show");
                                                    return false;
                                                });
    <?php } ?>
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                        <?php if ((($advancedCustom != false) && ($advancedCustom->disableShareAndPlaylist == false)) || ($advancedCustom == false)) { ?>
                            <div class="row bgWhite border border-light rounded bg-light" id="shareDiv">
                                <div class="tabbable-panel">
                                    <div class="tabbable-line text-muted">
                                        <div class="nav">
                                                <a class="nav-link " href="#tabShare" data-toggle="tab">
                                                    <span class="fa fa-share"></span>
                                                    <?php echo __("Share"); ?>
                                                </a>

                                            <?php
                                            if (empty($objSecure->disableEmbedMode)) {
                                                ?>
                                                    <a class="nav-link " href="#tabEmbed" data-toggle="tab">
                                                        <span class="fa fa-code"></span>
                                                        <?php echo __("Embed"); ?>
                                                    </a>
                                                <?php
                                            }
                                            ?>

                                                <a class="nav-link" href="#tabEmail" data-toggle="tab">
                                                    <span class="fa fa-envelope"></span>
                                                    <?php echo __("E-mail"); ?>
                                                </a>
                                                <a class="nav-link" href="#tabPermaLink" data-toggle="tab">
                                                    <span class="fa fa-link"></span>
                                                    <?php echo __("Permanent Link"); ?>
                                                </a>
                                        </div>
                                        <div class="tab-content clearfix">
                                            <div class="tab-pane active" id="tabShare">
                                                <?php
                                                $url = urlencode($global['webSiteRootURL'] . "{$catLink}video/" . $video['clean_title']);
                                                $title = urlencode($video['title']);
                                                include $global['systemRootPath'] . 'view/include/social.php';
                                                ?>
                                            </div>
                                            <div class="tab-pane" id="tabEmbed">
                                                <h4><span class="fas fa-share"></span> <?php echo __("Share Video"); ?>:</h4>
                                                <textarea class="form-control" style="min-width: 100%" rows="5"><?php
                                                    if ($video['type'] == 'video' || $video['type'] == 'embed') {
                                                        $code = '<iframe width="640" height="480" style="max-width: 100%;max-height: 100%;" src="' . Video::getLink($video['id'], $video['clean_title'], true) . '" frameborder="0" allowfullscreen="allowfullscreen" class="YouPHPTubeIframe"></iframe>';
                                                    } else {
                                                        $code = '<iframe width="350" height="40" style="max-width: 100%;max-height: 100%;" src="' . Video::getLink($video['id'], $video['clean_title'], true) . '" frameborder="0" allowfullscreen="allowfullscreen" class="YouPHPTubeIframe"></iframe>';
                                                    }
                                                    echo htmlentities($code);
                                                    ?>
                                                </textarea>
                                            </div>
                                            <div class="tab-pane" id="tabEmail">
                                                <?php if (!User::isLogged()) { ?>
                                                    <strong>
                                                        <a href="<?php echo $global['webSiteRootURL']; ?>user"><?php echo __("Sign in now!"); ?></a>
                                                    </strong>
                                                <?php } else { ?>
                                                    <form class="well " action="<?php echo $global['webSiteRootURL']; ?>sendEmail" method="post"  id="contact_form">
                                                        <fieldset>
                                                            <!-- Text input-->
                                                            <div class="form-inline mt-1">
                                                                <label class="col-4 col-form-label" for="email"><?php echo __("E-mail"); ?></label>
                                                                <div class="col-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-prepend"><div class="input-group-text"><span class="fas fa-envelope"></span></div></span>
                                                                        <input name="email" placeholder="<?php echo __("E-mail Address"); ?>" class="form-control"  type="text">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Text area -->

                                                            <div class="form-inline mt-1">
                                                                <label class="col-md-4 col-form-label"><?php echo __("Message"); ?></label>
                                                                <div class="col-md-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-prepend"><div class="input-group-text"><span class="fas fa-pencil-alt"></span></div></span>
                                                                        <textarea class="form-control" name="comment" placeholder="<?php echo __("Message"); ?>"><?php echo __("I would like to share this video with you:"); ?> <?php echo Video::getLink($video['id'], $video['clean_title']); ?></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-inline mt-1">
                                                                <label class="col-4 col-form-label"><?php echo __("Type the code"); ?></label>
                                                                <div class="col-8 inputGroupContainer">
                                                                    <div class="input-group">
                                                                        <span class="input-group-prepend"><img src="<?php echo $global['webSiteRootURL']; ?>captcha" id="captcha"></span>
                                                                        <span class="input-group-prepend"><span class="btn-sm btn-success" id="btnReloadCapcha"><span class="fas fa-sync"></span></span></span>
                                                                        <input name="captcha" placeholder="<?php echo __("Type the code"); ?>" class="form-control" type="text" maxlength="5" id="captchaText">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Button -->
                                                            <div class="form-inline mt-1">
                                                                <label class="col-4 col-form-label"></label>
                                                                <div class="col-8">
                                                                    <button type="submit" class="btn btn-primary" ><?php echo __("Send"); ?> <span class="fas fa-share-square"></span></button>
                                                                </div>
                                                            </div>

                                                        </fieldset>
                                                    </form>
                                                    <script>
                                                        $(document).ready(function () {
                                                            $('#btnReloadCapcha').click(function () {
                                                                $('#captcha').attr('src', '<?php echo $global['webSiteRootURL']; ?>captcha?' + Math.random());
                                                                $('#captchaText').val('');
                                                            });
                                                            $('#contact_form').submit(function (evt) {
                                                                evt.preventDefault();
                                                                modal.showPleaseWait();
                                                                $.ajax({
                                                                    url: '<?php echo $global['webSiteRootURL']; ?>objects/sendEmail.json.php',
                                                                    data: $('#contact_form').serializeArray(),
                                                                    type: 'post',
                                                                    success: function (response) {
                                                                        modal.hidePleaseWait();
                                                                        if (!response.error) {
                                                                            swal("<?php echo __("Congratulations!"); ?>", "<?php echo __("Your message has been sent!"); ?>", "success");
                                                                        } else {
                                                                            swal("<?php echo __("Your message could not be sent!"); ?>", response.error, "error");
                                                                        }
                                                                        $('#btnReloadCapcha').trigger('click');
                                                                    }
                                                                });
                                                                return false;
                                                            });
                                                        });
                                                    </script>
                                                <?php } ?>
                                            </div>

                                            <div class="tab-pane" id="tabPermaLink">
                                                <div class="form-inline col-12">
                                                    <label class="col-form-label col-4"><?php echo __("Permanent Link") ?></label>
                                                    <div class="col-8">
                                                        <input value="<?php echo Video::getPermaLink($video['id']); ?>" class="form-control" readonly="readonly"/>
                                                    </div>
                                                </div>
                                                <div class="form-inline col-12">
                                                    <label class="col-form-label col-4"><?php echo __("URL Friendly") ?> (SEO)</label>
                                                    <div class="col-8">
                                                        <input value="<?php echo Video::getURLFriendly($video['id']); ?>" class="form-control" readonly="readonly"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="row bgWhite border border-light rounded bg-light">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-lg-12">
                                    <div class="col-4 col-sm-2 col-lg-2 text-right"><strong><?php echo __("Category"); ?>:</strong></div>
                                    <div class="col-8 col-sm-10 col-lg-10"><a class="btn-sm btn-light"  href="<?php echo $global['webSiteRootURL']; ?>cat/<?php echo $video['clean_category']; ?>"><span class="<?php echo $video['iconClass']; ?>"></span> <?php echo $video['category']; ?></a></div>
                                    <div class="col-4 col-sm-2 col-lg-2 text-right"><strong><?php echo __("Description"); ?>:</strong></div>
                                    <div class="col-8 col-sm-10 col-lg-10" itemprop="description"><?php echo nl2br(textToLink(htmlentities($video['description']))); ?></div>
                                </div>
                            </div>

                        </div>
                        <script>
                            $(document).ready(function () {
                                $("#shareDiv").slideUp();
                                $("#shareBtn").click(function () {
                                    $("#shareDiv").slideToggle();
                                    return false;
                                });
                            });
                        </script>
                        <div class="row bgWhite border border-light rounded bg-light">
                            <?php include $global['systemRootPath'] . 'view/videoComments.php'; ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-4 bgWhite border border-light rounded bg-light rightBar">
                        <?php
                        if (!empty($playlist_id)) {
                            include $global['systemRootPath'] . 'view/include/playlist.php';
                            ?>
                            <script>
                                $(document).ready(function () {
                                    Cookies.set('autoplay', true, {
                                        path: '/',
                                        expires: 365
                                    });
                                });
                            </script>
                        <?php } else if (empty($autoPlayVideo)) {
                            ?>
                            <div class="col-lg-12 col-sm-12 col-12 autoplay text-muted" >
                                <div class="row autoplay text-muted" style="display: none;">
                                <strong class="col-4"><?php echo __("Autoplay ended"); ?></strong>
                                <span class="col-8">
                                    <label for="autoplay" class="font-weight-bold float-right">
                                            <span>
                                                <?php echo __("Autoplay"); ?>
                                                <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __("When autoplay is enabled, a suggested video will automatically play next."); ?>"></i>
                                            </span>
                                        </label>
                                </span>
                            </div>

                            </div>
                        <?php } else if (!empty($autoPlayVideo)) { ?>
                            <div class="row autoplay text-muted" style="display: none;">
                                <strong class="col-4"><?php echo __("Up Next"); ?></strong>
                                <span class="col-8">
                                    <div class="material-switch float-right">
                                        <input name="autoplay" id="autoplay" type="checkbox" value="1" class="pluginSwitch saveCookie"/>
                                        <label for="autoplay" class="badge-primary"></label>
                                    </div>
                                    <label for="autoplay" class="font-weight-bold float-right">
                                        <span>
                                            <?php echo __("Autoplay"); ?>
                                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __("When autoplay is enabled, a suggested video will automatically play next."); ?>"></i>
                                        </span>
                                    </label>
                                </span>
                            </div>
                            <div class="col-lg-12 col-sm-12 col-12 bottom-border autoPlayVideo" itemscope itemtype="http://schema.org/VideoObject" style="display: none;" >
                                <?php
                                echo youtubeModeVideoItem($autoPlayVideo);
                                ?>
                            </div>
                        <?php } if (!empty($advancedCustom->showAdsenseBannerOnLeft)) {
                            ?>
                            <div class="col-lg-12 col-sm-12 col-12">
                                <?php echo $config->getAdsense(); ?>
                            </div>
                        <?php } ?>
                        <div class="col-lg-12 col-sm-12 col-12 extraVideos nopadding"></div>
                        <!-- videos List -->
                        <div id="videosList">
                            <?php include $global['systemRootPath'] . 'view/videosList.php'; ?>
                        </div>
                        <!-- End of videos List -->

                        <script>
                            var fading = false;
                            var autoPlaySources = <?php echo json_encode($autoPlaySources); ?>;
                            var autoPlayURL = '<?php echo $autoPlayURL; ?>';
                            var autoPlayPoster = '<?php echo $autoPlayPoster; ?>';
                            var autoPlayThumbsSprit = '<?php echo $autoPlayThumbsSprit; ?>';
                            
                            $(document).ready(function () {
                                $("input.saveCookie").each(function () {
                                    var mycookie = Cookies.get($(this).attr('name'));
                                    if (mycookie && mycookie == "true") {
                                        $(this).prop('checked', mycookie);
                                        $('.autoPlayVideo').slideDown();
                                    }
                                });
                                $("input.saveCookie").change(function () {
                                    var auto = $(this).prop('checked');
                                    if (auto) {
                                        $('.autoPlayVideo').slideDown();
                                    } else {
                                        $('.autoPlayVideo').slideUp();
                                    }
                                    Cookies.set($(this).attr("name"), auto, {
                                        path: '/',
                                        expires: 365
                                    });
                                });
                                setTimeout(function () {
                                    $('.autoplay').slideDown();
                                }, 1000);
                            });
                        </script>
                    </div>
                    <div class="col-sm-1 col-md-1"></div>
                </div>
            <?php } else { ?>
                <div class="alert alert-warning">
                    <span class="glyphicon glyphicon-facetime-video"></span> <strong><?php echo __("Warning"); ?>!</strong> <?php echo __("We have not found any videos or audios to show"); ?>.
                </div>
            <?php } ?>
        </div>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
        <script>
                            /*** Handle jQuery plugin naming conflict between jQuery UI and Bootstrap ***/
                            $.widget.bridge('uibutton', $.ui.button);
                            $.widget.bridge('uitooltip', $.ui.tooltip);
        </script>
        <?php
        $videoJSArray = array("view/js/video.js/video.js");
        if ($advancedCustom != false) {
            $disableYoutubeIntegration = $advancedCustom->disableYoutubePlayerIntegration;
        } else {
            $disableYoutubeIntegration = false;
        }

        if ((isset($_GET['isEmbedded'])) && ($disableYoutubeIntegration == false)) {
            if ($_GET['isEmbedded'] == "y") {
                $videoJSArray[] = "view/js/videojs-youtube/Youtube.js";
            } else if ($_GET['isEmbedded'] == "v") {
                $videoJSArray[] = "view/js/videojs-vimeo/videojs-vimeo.js";
            }
        }
        $jsURL = combineFiles($videoJSArray, "js");
        ?>
        <script src="<?php echo $jsURL; ?>" type="text/javascript"></script>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        $videoJSArray = array("view/js/videojs-rotatezoom/videojs.zoomrotate.js", "view/js/videojs-persistvolume/videojs.persistvolume.js");
        $jsURL = combineFiles($videoJSArray, "js");
        ?>
        <script src="<?php echo $jsURL; ?>" type="text/javascript"></script>
    </body>
</html>
<?php include $global['systemRootPath'] . 'objects/include_end.php'; ?>
