<?php
/* Class to display single post
 */
require_once ('src/profile.php');

class Post
{
  private $forum;               // forum object
  private $session;             // session object
  private $db;                  // database object
  private $post_info;           // post information
  /*
    TODO: make final class out of this (pseudo-struct)
    post info array fields:
    pid:      post id
    uid:      user id of poster
    content:  content of the post
    controls: post action controls
    karma:    post karma information
    time:     post time
    edit:     edit time
  */

  /* Constructor */
  function Post ($forum, $session, $db, $post_info)
  {
    if ( !($forum instanceof Forum)
         || !($session instanceof Session)
         || !($db instanceof DB) )
      exit("Post instantiation failed: bad object");

    $this->forum = $forum;
    $this->session = $session;
    $this->db = $db;
    $this->post_info = $post_info;
  }

  /* Display post. */
  public function Display()
  {
    // Get poster information.
    $usr_info = $this->forum->GetCachedUser($this->post_info['uid']);

    // Get poster profile.
    $usrp = new UserProfile($usr_info);
    $prof = $usrp->Profile();

    // Get post.
    $this->FormatPost();
    $post = $this->GenPost($usr_info);

    PL( Div($prof . $post . Div('', array('class'=>'clear')),
            array('class'=>'post', 'id'=>"post{$this->post_info['pid']}")) );
  }

  /* Return pid */
  public function pid () { return $this->post_info['pid']; }

  /* Return tpid */
  public function tpid () { return $this->post_info['tpid']; }

  /* Format post information in preparation for display. */
  private function FormatPost()
  {
    $this->post_info['content'] = PrepContent($this->post_info['content'], TRUE);
    $this->PostControls();
    $this->PostTime();
    $this->PostKarma();
  }

  /* Generate button controls for post. */
  private function PostControls ()
  {
    $session_id = $this->session->GetUID();
    $post = $this->post_info;

    // Allow users to edit their own posts.
    $post_controls = "";
    if ($session_id == $post['uid'])
    {
      if ($post['tpid'] == 1)
        $post_controls .= Button("edit", array('onclick'=>"editPost({$post['pid']}, \"edit_edit\", 1)"));
      else
        $post_controls .= Button("edit", array('onclick'=>"editPost({$post['pid']}, \"edit_edit\", 0)"));
    }
    // If user hasn't modified karma of this post yet, display karma buttons.
    else if ($this->db->PostKarmaChangeAllowed($post['pid'], $session_id))
    {
      $post_controls .= Button(Karma::PLUS, array('onclick'=>"karma(\"karma_plus\", {$post['pid']}, {$post['uid']})", 'class'=>'plus'))
        . " " . Button(Karma::MINUS, array('onclick'=>"karma(\"karma_minus\", {$post['pid']}, {$post['uid']})", 'class'=>'minus'));
    }

    $post_controls .= " " . Button("quote", array('onclick'=>"quotePost({$post['pid']})"));

    $this->post_info['controls'] = $post_controls;
  }

  /* Format time for post. */
  private function PostTime ()
  {
    // Post times.
    $post = $this->post_info;
    $edit_time = "";
    if (isset($post['edit']) && $post['edit'] != 0)
    {
      $edit_time = "edited " . GetTime(TIME_FULL, $post['edit']);
    }
    // Edit time needs an id, since it can change dynamically.
    $edit_time = Tag("label", $edit_time, array('id'=>"edittime{$post['pid']}"));
    $post_time = "posted " . GetTime(TIME_FULL, $post['time']);

    $this->post_info['time'] = $edit_time . "</br>" . $post_time;
  }

  /* Format karma information for post. */
  private function PostKarma ()
  {
    $post_karma = $this->db->GetPostKarma($this->post_info['pid']);
    $karma_info = array('plus_karma'=>'', 'minus_karma'=>'');
    $plus_names = array();
    $minus_names = array();

    // For all the karma applied to the post, find the user and organize into postive and
    // negative karma.
    foreach ($post_karma as $karma)
    {
      $user_info = $this->forum->GetCachedUser($karma['uid']);
      if ($karma['type'] === 'plus')
        array_push($plus_names, hLink(Pages::USER."?uid={$user_info['uid']}", $user_info['name']));
      else
        array_push($minus_names, hLink(Pages::USER."?uid={$user_info['uid']}", $user_info['name']));
    }

    // Assemble positive and negative karma lists.
    if (0 < count($plus_names))
    {
      $karma_info['plus_karma'] = Karma::PLUSact . " by: " . implode(", ", $plus_names);
    }
    if (0 < count($minus_names))
    {
      $karma_info['minus_karma'] = Karma::MINUSact . " by: " . implode(", ", $minus_names);
    }

    $this->post_info['karma'] = $karma_info;
  }

  /* Generate HTML for post content. */
  private function GenPost($usr_info)
  {
    $pid = $this->post_info['pid'];

    // Post text.
    $content = Div( $this->post_info['content'], array('class'=>'post_text') );

    // Edit area.
    $edit = Div('', array('class'=>'post_edit'));

    // User signature.
    $sig = Div( PrepContent($usr_info['signature'], FALSE),
                array('class'=>"post_sig user{$usr_info['uid']}_sig") );

    // Post karma information
    $karma = Div( $this->post_info['karma']['plus_karma'], array('class'=>'post_karma_plus') )
      . Div( $this->post_info['karma']['minus_karma'], array('class'=>'post_karma_minus') );

    // Post footer (time and controls).
    $footer = Div( Div( $this->post_info['time'], array('class'=>'post_time') )
                   . Div( $this->post_info['controls'], array('class'=>'post_controls') )
                   ,
                   array('class'=>'post_footer') );

    return Div( Div($content . $edit . STag('hr') . $sig . $karma . $footer,
                    array('class'=>'post_content container')),
                array('class'=>'content_box') );
  }

}
?>