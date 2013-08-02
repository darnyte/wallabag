<?php
/**
 * poche, a read it later open source system
 *
 * @category   poche
 * @author     Nicolas Lœuillet <support@inthepoche.com>
 * @copyright  2013
 * @license    http://www.wtfpl.net/ see COPYING file
 */

include dirname(__FILE__).'/inc/config.php';

myTool::initPhp();

# XSRF protection with token
if (!empty($_POST)) {
    if (!Session::isToken($_POST['token'])) {
        die(_('Wrong token.'));
    }
    unset($_SESSION['tokens']);
}

$ref = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];

if (isset($_GET['login'])) {
    // Login
    if (!empty($_POST['login']) && !empty($_POST['password'])) {
        if (Session::login($_SESSION['login'], $_SESSION['pass'], $_POST['login'], encode_string($_POST['password'] . $_POST['login']))) {
            logm('login successful');
            $msg->add('s', 'welcome in your poche!');
            if (!empty($_POST['longlastingsession'])) {
                $_SESSION['longlastingsession'] = 31536000;
                $_SESSION['expires_on'] = time() + $_SESSION['longlastingsession'];
                session_set_cookie_params($_SESSION['longlastingsession']);
            } else {
                session_set_cookie_params(0); // when browser closes
            }
            session_regenerate_id(true);

            MyTool::redirect($ref);
        }
        logm('login failed');
        die(_("Login failed !"));
    } else {
        logm('login failed');
    }
}
elseif (isset($_GET['logout'])) {
    logm('logout');
    Session::logout();
    MyTool::redirect();
}
elseif  (isset($_GET['config'])) {
    if (isset($_POST['password']) && isset($_POST['password_repeat'])) {
        if ($_POST['password'] == $_POST['password_repeat'] && $_POST['password'] != "") {
            logm('password updated');
            if (!MODE_DEMO) {
                $store->updatePassword(encode_string($_POST['password'] . $_SESSION['login']));
                $msg->add('s', _('your password has been updated'));
            }
            else {
                $msg->add('i', _('in demo mode, you can\'t update password'));
            }
        }
        else
            $msg->add('e', _('your password can\'t be empty and you have to repeat it in the second field'));
    }
}

# Traitement des paramètres et déclenchement des actions
$view               = (isset ($_REQUEST['view'])) ? htmlentities($_REQUEST['view']) : 'index';
$full_head          = (isset ($_REQUEST['full_head'])) ? htmlentities($_REQUEST['full_head']) : 'yes';
$action             = (isset ($_REQUEST['action'])) ? htmlentities($_REQUEST['action']) : '';
$_SESSION['sort']   = (isset ($_REQUEST['sort'])) ? htmlentities($_REQUEST['sort']) : 'id';
$id                 = (isset ($_REQUEST['id'])) ? htmlspecialchars($_REQUEST['id']) : '';
$url                = (isset ($_GET['url'])) ? $_GET['url'] : '';

$tpl->assign('isLogged', Session::isLogged());
$tpl->assign('referer', $ref);
$tpl->assign('view', $view);
$tpl->assign('poche_url', myTool::getUrl());
$tpl->assign('demo', MODE_DEMO);
$tpl->assign('title', _('poche, a read it later open source system'));

if (Session::isLogged()) {
    action_to_do($action, $url, $id);
    display_view($view, $id, $full_head);
}
else {

    $tpl->draw('login');
}
