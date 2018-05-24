<?php
if(!session_id()) {
    session_start();
}
require_once 'includes/imp_files.php';
require_once 'vendor/autoload.php';
$fb = new Facebook\Facebook([
    'app_id' => APP_ID,
    'app_secret' => APP_SECRET,
    'default_graph_version' => 'v2.12',
]);
$helper = $fb->getRedirectLoginHelper();
if (isset($_GET['state'])) {
    $helper->getPersistentDataHandler()->set('state', $_GET['state']);
}
//$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; // optional

try {
    if (isset($_SESSION['facebook_access_token'])) {
        $accessToken = $_SESSION['facebook_access_token'];
    } else {
        $accessToken = $helper->getAccessToken();
    }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}
if (isset($accessToken)) {
    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        // getting short-lived access token
        $_SESSION['facebook_access_token'] = (string) $accessToken;
        // OAuth 2.0 client handler
        $oAuth2Client = $fb->getOAuth2Client();
        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);
        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;
        // setting default access token to be used in script
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }
    // redirect the user back to the same page if it has "code" GET variable
    if (isset($_GET['code'])) {
        header('Location: ./');
    }
    // getting basic info about user
    try {
        $profile_request = $fb->get('/me?fields=name,first_name,last_name,email,picture');
        $profile = $profile_request->getGraphNode()->asArray();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        // redirecting user back to app login page
        header("Location: ./");
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    // printing $profile array on the screen which holds the basic info about user

    $name = isset($profile['name']) ? $profile['name'] : null;
    $first_name = isset($profile['first_name']) ? $profile['first_name'] : null;
    $last_name = isset($profile['last_name']) ? $profile['last_name'] : null;
    $email = isset($profile['email']) ? $profile['email'] : null;
    $fb_id = isset($profile['id']) ? $profile['id'] : null;
    $profile_pic = isset($profile['picture']['url']) ? $profile['picture']['url'] : IMG_DIR.'/avavtardefault50px.png';

    $_SESSION['first_name'] = $first_name;
    $_SESSION['full_name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['fb_id'] = $fb_id;
    $_SESSION['profile_pic'] = $profile_pic;

    // Now you can redirect to another page and use the access token from $_SESSION['facebook_access_token']

} else {
    // replace your website URL same as added in the developers.facebook.com/apps e.g. if you used http instead of https and you used non-www version or www version of your website then you must add the same here
    $loginUrl = $helper->getLoginUrl('http://localhost/files/Bitcoin_Projects/bc_exchange/fbconfig.php', $permissions);
}