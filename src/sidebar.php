<?php
/* Forum sidebar class. Will be used on most pages. Contains
 * useful links and information. Appears when mouse hovers
 * over staging area, disappears when mouse leaves sideabr.
 */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once('include/common_cfg.php');

class Sidebar
{
  private $forum;               // forum object
  private $session;             // user session object
  private $sb_info =            // sidebar information
    array(
      'welcome' => '',
      'chat' => '',
      'board' => '',
      'bookmark' => '',
      'msg' => '',
      'cur_usrs' => '',
      'day_usrs' => '',
      'logout' => '',
      'ver' => '',
    );

  public function Sidebar($forum, $session)
  {
    if ( !($forum instanceof Forum) || !($session instanceof Session) )
      exit("Sidebar instantiation failed: bad forum or session object");
    $this->forum = $forum;
    $this->session = $session;
  }

  /* Display the sidebar. */
  public function Display()
  {
    $this->GetSBInfo();

    // Hover area to trigger sidebar.
    $trigger = Div('sidebar', array('class'=>'sbtrig_txt'));
    PL( Div($trigger, array('class'=>'sbtrig', 'id'=>'sbtrig')) );

    // Sidebar.
    $sidebar =
      Div($this->sb_info['welcome'], array('class'=>'sb_head', 'id'=>'sb_welc'))
      //. Div($this->sb_info['chat'], array('class'=>'sidebar_item', 'id'=>'sidebar_chat'))
      . Div($this->sb_info['board'], array('class'=>'sb_elem'))
      . Div($this->sb_info['bookmark'], array('class'=>'sb_elem'))
      . Div($this->sb_info['msg'], array('class'=>'sb_elem'))
      . Div($this->sb_info['cur_usrs'], array('class'=>'sb_elem sb_usrs'))
      . Div($this->sb_info['day_usrs'], array('class'=>'sb_elem sb_usrs'))
      . Div($this->sb_info['logout'], array('class'=>'sb_elem'))
      . Div($this->sb_info['ver'], array('class'=>'sb_elem sb_last', 'id'=>'sb_ver'));
    PL( Div($sidebar, array('class'=>'sidebar', 'id'=>'sidebar')) );
  }

  // Fill in sidebar information to be displayed.
  private function GetSBInfo()
  {
    // List currently online users.
    $cur_usrs = $this->forum->GetOnlineUsers(15);
    $cur_usr_links = array();
    foreach ($cur_usrs as $user)
      array_push ($cur_usr_links, UsrLink($user['uid'], $user['name']));
    $cur_usr_str = "online users</br>";
    $cur_usr_str .= (count($cur_usr_links) == 0)? "none" : implode(", ", $cur_usr_links);

    // List past day's users.
    $day_usrs = $this->forum->GetOnlineUsers(1440);
    $day_usr_links = array();
    foreach ($day_usrs as $user)
      array_push ($day_usr_links, UsrLink($user['uid'], $user['name']));
    $day_usr_str = "users in past day</br>";
    $day_usr_str .= (count($day_usr_links) == 0)? "none" : implode(", ", $day_usr_links);

    $this->sb_info['welcome'] =
      "Welcome</br>"
      . UsrLink($this->session->GetUID(), $this->session->GetUserName())
      . "!";
    $this->sb_info['chat'] = $this->forum->GenerateChat();
    $this->sb_info['board'] = hLink(Pages::BOARD, "board");
    $this->sb_info['bookmark'] = UsrLink($this->session->GetUID(), "bookmarks", Profile::FAV);
    $this->sb_info['msg'] = UsrLink($this->session->GetUID(), "messages", Profile::MSG);
    $this->sb_info['cur_usrs'] = $cur_usr_str;
    $this->sb_info['day_usrs'] = $day_usr_str;
    $this->sb_info['logout'] = hLink(Pages::ACTION."?action=logout", "logout");
    $this->sb_info['ver'] = "LOLBros beta " . hLink("changelog.txt", "v" . VERSION);
  }
}