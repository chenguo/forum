<?php
/* Class to display user profile
 *
 * Needs array of user info. Fields:
 *   uid:      user id
 *   name:     name
 *   posts:    post count
 *   avatar:   link to avatar
 *   t_online: last online time
 *   plus:     positive karma
 *   minus:    negative karma
 *   posts_display: posts to show per page
 *   threads_display: threads to show per page
 *   signature: user signature
 */

require_once ('src/util.php');
require_once ('src/html.php');

class UserProfile
{
  private $forum;               // forum object
  private $usr_info;            // user information

  function UserProfile ($usr_info)
  {
    $this->usr_info = $usr_info;
  }

  /* Generate user profile. */
  public function Profile ()
  {
    // User name.
    $name = Div( UsrLink($this->usr_info['uid'], $this->usr_info['name']), array('class'=>'usrp_name') );
    $avatar = Img( $this->usr_info['avatar'], array('class'=>'usrp_avatar') );
    $post_cnt = Div( $this->usr_info['posts'] . ' posts', array('class'=>'usrp_posts') );
    $karma_p = Div( Div( $this->usr_info['plus'], array('class'=>'usrp_karma_p')) . ' ' . Karma::PLUSpl,
                    array('class'=>'usrp_karma'));
    $karma_m = Div( Div( $this->usr_info['minus'], array('class'=>'usrp_karma_m')) . ' ' . Karma::MINUSpl,
                    array('class'=>'usrp_karma'));

    $profile = Div( Div( $name . $avatar . $post_cnt . $karma_p . $karma_m,
                         array('class'=>"usrp container user_prof_{$this->usr_info['uid']}") ),
                    array('class'=>'usrp_box') );
    return $profile . "\n";
  }
}
?>