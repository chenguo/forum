<?php
ini_set('display_errors', 1); error_reporting( E_ALL | E_STRICT );
require_once ('src/common_cfg.php');
require_once ('src/html.php');
require_once ('src/page.php');
require_once ('src/profile.php');
require_once ('src/sidebar.php');
require_once ('src/threadlist.php');

class User extends Page
{

  private $uid;                       // User ID.
  private $user_info;                 // User info.
  private $subpage = SUBP::DETAILS;   // Profile subpage.
  private $subpage_funcs = array (
    SUBP::DETAILS => "User::SubProfDetails",
    SUBP::SETTINGS => "User::SubProfEdits",
    SUBP::RECENT => "User::SubProfRecent",
    SUBP::FAV => "User::SubProfFav",
    SUBP::PRIVMSG => "User::SubProfPrivMsg"
    );

  /* Constructor */
  function User ($forum, $session, $db)
  {
    if ( !($forum instanceof Forum)
         || !($session instanceof Session)
         || !($db instanceof DB) )
    {
      exit("Page instantiation failed: bad object");
    }

    // Set up class variables
    $this->forum = $forum;
    $this->session = $session;
    $this->db = $db;
    $this->sidebar = new Sidebar($forum, $session);

    // Lists of files to include
    $this->css = array(CSS::COMMON, CSS::USER, CSS::SIDEBAR, CSS::BOARD);
    $this->js = array(JS::JQUERY, JS::COMMON, JS::USER, JS::SIDEBAR);
  }

  /* Actions taken on page request, before displaying page. */
  protected function LoadAction()
  {
    $ret = FALSE;

    // Make sure user is logged in.
    if ( !$this->session->CheckLogin(TRUE) || $this->session->GetUID() <= 0 )
    {
      header("LOCATION: index.php");
    }
    // Make sure user ID is valid for profile.
    else if (!isset($_REQUEST['uid']) || $_REQUEST['uid'] < 0)
    {
      header("LOCATION: threads.php");
    }
    else
    {
      $this->uid = $_REQUEST['uid'];
      $this->user_info = $this->forum->GetCachedUser($this->uid);
    }

    // Handle actions.
    if (isset($_REQUEST['action']))
    {
      $this->HandleAction();
    }
    else
    {
      $ret = TRUE;
    }

    return $ret;
  }

  /* Display body of thread page. */
  protected function DisplayBody ()
  {
    PL( STag('body') );

    // Banner.
    PL( $this->Banner() );

    // Sidebar.
    $this->sidebar->Display();

    // Profile left column
    PL ($this->LeftCol());

    // Subpage.
    PL ($this->Subpage());

    PL( STag('/body') );
  }

  /* Display left column of profile page */
  protected function LeftCol()
  {
    $prof = $this->Profile();
    $links = $this->ProfLinks();
    return Div($prof . $links, array('class' => 'usrp_leftcol'));
  }

  /* Display user profile */
  protected function Profile ()
  {
    $user_info = $this->forum->GetCachedUser($this->uid);
    $profile = new UserProfile ($user_info);
    return $profile->Profile();
  }

  /* Display links to sub-pages */
  protected function ProfLinks ()
  {
    $cur_uid = $this->session->GetUID();
    $links = Tag("li", hLink("javascript:void(0)", "profile",
                                array('onclick'=>"userProfView(\"profile\", $this->uid)")));

    /* Links for user viewing their own profiles. */
    if ($cur_uid == $this->uid)
      {
        $links .= Tag("li", hLink("javascript:void(0)", "edit profile",
                                     array('onclick'=>"userProfView(\"edit\", $this->uid)")));
      }

    $links .= Tag("li", hLink("javascript:void(0)", "recent",
                                 array('onclick'=>"userProfView(\"recent\", $this->uid)")));

    /* More links for users viewing their own profiles. */
    if ($cur_uid == $this->uid)
      {
        $links .= Tag("li", hLink("javascript:void(0)", "favorites",
                                     array('onclick'=>"userProfView(\"favorite\", $this->uid)")))
          . Tag("li", hLink("javascript:void(0)", "messages",
                               array('onclick'=>"userProfView(\"message\", $this->uid)")));
      }

    return Div( Div( Tag("ul", $links),
                     array('class'=>'usrp_links container')),
                array('class'=>'usrp_links_box'));
  }

  /*******************************\
   *                             *
   *      Subpages Display       *
   *                             *
  \*******************************/

  /* Display sub-page of profile. */
  private function SubPage ()
  {
    $page = $_REQUEST['view'];
    if ($page === 'settings')
      $this->subpage = SUBP::SETTINGS;
    else if ($page === 'recent')
      $this->subpage = SUBP::RECENT;
    else if ($page === 'fav')
      $this->subpage = SUBP::FAV;
    else if ($page === 'msg')
      $this->subpage = SUBP::PRIVMSG;
    else
      $this->subpage = SUBP::DETAILS;

    if ($this->subpage == SUBP::DETAILS)
      $subpage = $this->SubProfDetails();
    else if ($this->subpage == SUBP::SETTINGS)
      $subpage = $this->SubProfEdits();
    else if ($this->subpage == SUBP::RECENT)
      $subpage = $this->SubProfRecent();
    else if ($this->subpage == SUBP::FAV)
      $subpage = $this->SubProfFav();
    else if ($this->subpage == SUBP::PRIVMSG)
      $subpage = $this->SubProfPrivMsg();

    PL( Div($subpage, array('class'=>'usrp_subp_box')) );
  }

  /* Generate user details sub profile page. */
  private function SubProfDetails ()
  {
    $user_details_table =
      Tag("table",
          Tag("tr", Tag("th", "", array('class'=>'usrp_tbl_label'))
              . Tag("th", "", array('class'=>'usrp_tbl_value')))
          . Tag("tr", Tag("td","email") . Tag("td",$this->user_info['email']) )
          . Tag("tr", Tag("td","profile views") . Tag("td",$this->user_info['views']) )
          . Tag("tr", Tag("td","birthday") . Tag("td",$this->user_info['birth']) )
          . Tag("tr", Tag("td","joined on") . Tag("td",$this->user_info['join']) )
          . Tag("tr", Tag("td","last login") . Tag("td",$this->user_info['t_online']) )
          . Tag("tr", Tag("td","signature") . Tag("td",PrepContent($this->user_info['signature'], FALSE)) )
          ,
          array('class'=>'noshow usrp_tbl'));
    return Div($user_details_table, array('class'=>'container'));
  }

  /* Generate eid profile sub profile page. */
  private function SubProfEdit ()
  {
    return $this->SubProfDetailsEdit() . $this->SubProfPassword();
  }

  // Generate edit profile sub profile page. */
  private function SubProfDetailsEdit()
  {
    $msg_div = Div("", array('class'=>'usrp_edit_msg'));

    // Header row (not shown)
    $header = TabRow( TabHdr("", array('class'=>'usrp_tbl_label'))
                         . TabHdr("", array('class'=>'usrp_tbl_value')) );

    // Table of input fields for basic settings
    $basic_settings =
      Table($header
            // email
            . Tabrow( TabCol("email")
                        . TabCol("<input type='text' id='email' value={$this->user_info['email']}>"))
            // posts per page
            . Tabrow( TabCol("posts per page")
                        . TabCol("<input type='text' id='post_disp' value={$this->user_info['posts_display']}>"))
            // threads per page
            . Tabrow( TabCol("threads per page")
                        . TabCol("<input type='text' id='thr_disp' value={$this->user_info['threads_display']}>"))
            // avatar
            . Tabrow( TabCol("avatar")
                        . TabCol("<input type='text' id='avatar' value={$this->user_info['avatar']}>"))
            // signature
            . Tabrow( TabCol("signature")
                        . TabCol(Tag("textarea", $this->user_info['signature'], array('class'=>'prof_edit_sig', 'maxlength'=>'255'))))
            ,
            array('class'=>'noshow usrp_tbl'));
    $save_button = Button("save", array('onclick'=>"userProfSave({$this->user_info['uid']})", 'class'=>'settings_btn'));
    $cancel_button = Button("cancel", array('onclick'=>"userProfCancel({$this->user_info['uid']})", 'class'=>'settings_btn'));

    return Div($msg_div . $basic_settings . $save_button . $cancel_button,
               array('class'=>'container'));
  }

  /* Generate password change sub profile page. */
  private function SubProfPassword()
  {
    $msg_div = Div("", array('class'=>'usrp_pw_msg'));

    // Header row (not shown)
    $header = TabRow( TabHdr("", array('class'=>'usrp_tbl_label'))
                      . TabHdr("", array('class'=>'usrp_tbl_value'))
                        );

    // Password change fields
    $password_table =
      table($header
            . TabRow( TabCol("current password")
                        . TabCol("<input type='password' id='cur_pw'>"))
            . TabRow( TabCol("new password")
                        . TabCol("<input type='password' id='new_pw'>"))
            . TabRow( TabCol("confirm new password")
                        . TabCol("<input type='password' id='cnf_pw'>")),
            array('class'=>'noshow usrp_tbl')
            );
    $pw_button = Button("change", array('onclick'=>"userProfPW({$this->user_info['uid']})", 'class'=>'settings_btn'));
    return Div($msg_div . $password_table . $pw_button,
               array('class'=>'container'));
  }

  /* */
  private function SubProfRecent()
  {
    return $this->SubProfRecentPosts()
      . $this->SubProfRecentKarma(TRUE)
      . $this->SubProfRecentKarma(FALSE);
  }

  // Generate a list of user's recent posts
  private function SubProfRecentPosts()
  {
    $recent_posts_array = $this->forum->GenerateUserRecentPosts($this->uid);
    $rows = "";
    // Tabularize posts
    foreach ($recent_posts_array as $post)
      $rows .= TabRow( TabCol($post['post'] . Div($post['time'], array('class'=>'time'))) . TabCol($post['content']));
    $recent_posts = Tag("h2", "Recent Posts") . table($rows, array('class'=>'usrp_tbl'));

    return Div($recent_posts, array('class'=>'container'));
  }

  // Generate a list of user's recent posts
  private function SubProfRecentKarma($received)
  {
    $recent_karma = "";
    $karma_array;
    if ($received == 0)
      $karma_array = $this->forum->GenerateUserRecentKarmaGiven($this->uid);
    else
      $karma_array = $this->forum->GenerateUserRecentKarmaRecvd($this->uid);

    // Tabularize list of karma actions
    $rows = "";
    foreach ($karma_array as $karma_info)
      {
        $rows .= TabRow( TabCol($karma_info['action'])
                         . TabCol($karma_info['recip'])
                         . TabCol($karma_info['thread'])
                         . TabCol(Div($karma_info['time'], array('class'=>'time'))));
      }

    // Add a header
    if ($received == 0)
      $recent_karma = Tag("h2", "Recent Karma Given") . table($rows, array('class'=>'usrp_tbl'));
    else
      $recent_karma = Tag("h2", "Recent Karma Received") . table($rows, array('class'=>'usrp_tbl'));

    return Div( $recent_karma, array('class'=>'container'));
  }

  /* Generate user's favorite threads. */
  private function SubProfFav ()
  {
    $threads = $this->db->GetUserFavThreads($this->uid);
    $threadlist = new ThreadList ($this->session, $this->db, $threads);

    return Div( Tag("h2", "Favorite Threads"),
                array('class'=>'container'))
      . Div($threadlist->GenerateThreadList(0),
            array('class'=>'container'));
  }

  // Generate user's private messages.
  private function SubProfPrivMsg ()
  {
    return Div( Tag("h2", "Private Messages"), array('class'=>'container'));
  }

  /*******************************\
   *                             *
   *       Action Handling       *
   *                             *
  \*******************************/
  /* Handle actions */
  private function HandleAction()
  {
    $action = $_REQUEST['action'];
    $resp = "";

    // Subprofile viewing
    if ($action == 'usrp_prof')
      $resp = $this->SubProfDetails ();
    else if ($action == 'usrp_edit')
      $resp = $this->SubProfEdit ();
    else if ($action == 'usrp_recent')
      $resp = $this->SubProfRecent ();
    else if ($action == 'usrp_fav')
      $resp = $this->SubProfFav ();
    else if ($action == 'usrp_msgs')
      $resp = $this->SubProfPrivMsg ();
    // Updating user profile
    else if ($action == 'usrp_save'
             || $action == 'usrp_cancel')
      $resp = $this->UserProfUpdate();
    // Update password
    else if ($action == 'usrp_pw_change')
      $resp = $this->UserProfPW();

    echo $resp;
  }

  /* Update user profile */
  private function UserProfUpdate()
  {
    if (isset($_REQUEST['uid']) && $_REQUEST['uid'] == $this->uid)
    {
      $user_info;
      if ($_REQUEST['action'] == 'usrp_save')
      {
        $new_user_info = array();
        if (isset($_POST['email']))
          $new_user_info['email'] = "\"" . $_POST['email'] . "\"";
        if (isset($_POST['thr_disp']) && intval($_POST['thr_disp']) > 0)
          $new_user_info['threads_display'] = $_POST['thr_disp'];
        if (isset($_POST['post_disp']) && intval($_POST['post_disp']) > 0)
          $new_user_info['posts_display'] = $_POST['post_disp'];
        if (isset($_POST['avatar']))
          $new_user_info['avatar'] = "\"" . $_POST['avatar'] . "\"";
        if (isset($_POST['sig']))
          $new_user_info['signature'] = $_POST['sig'];
        $user_info = $this->db->UpdateUserProfile($this->uid, $new_user_info);
      }
      else if ($_REQUEST['action'] == 'usrp_cancel')
      {
        $user_info = $this->db->GetUserProfile($this->uid, TRUE, FALSE);
      }

      echo json_encode(array('email'=>$user_info['email'],
                             'avatar'=>$user_info['avatar'],
                             'post_disp'=>$user_info['posts_display'],
                             'thr_disp'=>$user_info['threads_display'],
                             'sig'=>$user_info['signature']));
    }
  }

  /* Update user password */
  private function UserProfPW()
  {
    $success = FALSE;

    if (isset($_POST['cur_pw'])
        && isset($_POST['new_pw'])
        && isset($_REQUEST['uid'])
        && $_REQUEST['uid'] == $this->uid
        && $this->session->CheckPassword($_POST['cur_pw']))
    {
      $update_fields = array('password'=>"\"" . md5($_POST['new_pw']) . "\"");
      $user_info = $this->db->UpdateUserProfile($this->uid, $update_fields);
      $success = TRUE;
    }

    if ($success == TRUE) return '0';
    else return '1';
  }

}

$user = new User($forum, $session, $db);
$user->Display();
exit();


?>
