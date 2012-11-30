<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once ('src/defines.php');
require_once ('src/common_cfg.php');
require_once ('src/page.php');
require_once ('src/sidebar.php');
require_once ('src/post.php');

class Thread extends Page
{
  private $page = 1;                // page number to display
  private $tid;                     // thread id to display
  private $title;                   // thread title
  private $pg_links;                // links to thread pages
  private $fav;                     // favorite icon
  private $posts = array();         // array of post objects

  /* Constructor. */
  function Thread ($forum, $session, $db)
  {
    if ( !($forum instanceof Forum)
         || !($session instanceof Session)
         || !($db instanceof DB) )
    {
      exit("Page instatiantion failed: bad object");
    }

    // Set up class variables.
    $this->forum = $forum;
    $this->session = $session;
    $this->db = $db;
    $this->sidebar = new Sidebar($forum, $session);

    // List of files to include.
    $this->css = array(CSS::COMMON, CSS::THREAD, CSS::SIDEBAR);
    $this->js = array(JS::JQUERY, JS::COMMON, JS::THREAD, JS::SIDEBAR);
  }

  /* Actions taken on page request, before displaying page. */
  protected function LoadAction()
  {
    $ret = FALSE;

    // Make sure user is logged in.
    if ( !$this->session->CheckLogin(TRUE) || $this->session->GetUID() <= 0 )
    {
      header ("LOCATION: " . Pages::LOGIN);
    }
    // Handle post actions.
    else if ( isset($_REQUEST['action']) )
    {
      $this->HandleAction();
    }
    // Make sure there is a thread ID to display
    else if ( !isset($_REQUEST['tid']) || $_REQUEST['tid'] <= 0 )
    {
      header("LOCATION: " . Pages::BOARD);
    }
    // Get thread and page to display.
    else
    {
      $this->tid = $_REQUEST['tid'];
      if ( isset($_REQUEST['page']) )
      {
        if ( ($this->page = intval($_REQUEST['page'])) < 1 )
          $this->page = 1;
      }
      $ret = TRUE;
    }

    return $ret;
  }

  /* Display body of thread page. */
  protected function DisplayBody ()
  {
    $this->ThreadInfo();

    PL( STag('body') );

    // Banner.
    PL( $this->Banner() );

    // Sidebar.
    $this->sidebar->Display();

    // Titlebar.
    PL( $this->Titlebar() );

    // Posts.
    $this->DisplayPosts();

    // Titlebar again.
    PL( $this->Titlebar() );

    // New post form.
    PL( $this->PostForm() );

    PL( STag('/body') );
  }

  /* Generate page title. */
  protected function Title ()
  {
    return Tag('title', BOARD_NAME . " - " . $this->title);
  }

  /* Generate thread titlebar. */
  private function Titlebar ()
  {
    return Div( Div(hLink(Pages::BOARD, 'board'), array('class'=>'thr_brd'))
                . Div($this->pg_links, array('class'=>'thr_pg'))
                . Div($this->title, array('class'=>'thr_ttl'))
                . Div($this->fav,  array('class'=>'thr_fav')),
                array('class'=>'title_bar container'));
 }

  /* Display posts in thread. */
  private function DisplayPosts ()
  {
    foreach ($this->posts as $post)
      $post->Display();
  }

  /* Get display information for thread. */
  private function ThreadInfo ()
  {
    $nposts = $this->session->posts_per_page;
    $uid = $this->session->GetUID();
    $tid = $this->tid;

    // Get thread and list of post infos.
    $thread = $this->db->GetThread($tid, TRUE /* update viewcount */);
    $this->title = $thread['title'];
    $this->pg_links = MakePageLinks($this->page, $nposts, $thread['posts'], Pages::THREAD."?tid={$tid}");

    // Get list of posts in thread.
    $posts_array = $this->db->GetPosts($tid, $this->page, $this->session->posts_per_page);

    // Make objects out of the posts.
    foreach ($posts_array as $post_info)
    {
      array_push($this->posts, new Post($this->forum, $this->session, $this->db, $post_info));
    }

    // Mark user as at least having read the last post on current page.
    $last_post = end($this->posts);
    $this->db->UpdateUserPostView($uid, $tid, $last_post->pid(), $last_post->tpid());

    // Favorite status.
    if ($this->db->GetThreadUserFav($tid, $uid))
    {
      $this->fav = hLink("javascript:void(0)",
                      Img("/imgs/site/star_filled.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(0,$uid,$tid)")));
    }
    else
    {
      $this->fav = hLink("javascript:void(0)",
                            Img("/imgs/site/star_empty.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(1,$uid,$tid)")));
    }
  }

  /* Generate new post form */
  private function PostForm()
  {
    $form =
      Div('', array('id'=>'new_post_preview'))
      . Tag('form',
            Tag('textarea', '', array('class'=>'new_post', 'name'=>'content', 'id'=>'newpost_form'))
            . "<input type='hidden' name='tid' value='$this->tid'>"
            . '<input type="hidden" name="action" value="post">'
            . '<input type="submit" value="submit" class="button new_post_button">'
            . "<input type='button' value='preview' class='button preview_post_button' onclick='previewNewPost($this->tid)'>",
            array('id'=>'new_post', 'name'=>'post', 'action'=>Pages::THREAD, 'method'=>'post',
                  'onsubmit'=>'button.disabled'));
    return $form;
  }


  /*****************************************\
   *                                       *
   *             Action Handling           *
   *                                       *
  \*****************************************/

  /* Handle actions. */
  private function HandleAction()
  {
    if ( 0 > ($uid = $this->session->GetUID()) )
    {
      return;
    }

    $action = $_REQUEST['action'];
    if ( $action === 'post' )
    {
      $this->SubmitNewPost($uid);
    }
    else if ( $action === 'new_post_preview' )
    {
      $this->PreviewNewPost($uid);
    }
    // All further actions require PID
    else if ( isset($_REQUEST['pid']) )
    {
      $pid = $_REQUEST['pid'];
      $post_uid = $this->db->GetPostUID($pid);
      if ($post_uid < 0)
        exit();

      // Apply karma
      if ($action === 'karma_plus' || $action === 'karma_minus')
      {
        $this->KarmaAction($uid, $action);
      }
      else if ($action === 'quote')
      {
        $this->QuotePost($pid, $post_uid);
      }
      else if ( ($post_uid === $uid)
                && ($action === 'edit_edit'
                    || $action === 'edit_preview'
                    || $action === 'edit_cancel'
                    || $action === 'edit_submit') )
      {
        $this->EditPost($pid, $uid, $action);
      }
    }
  }

  /* Submit new post */
  private function SubmitNewPost($uid)
  {
    // Ensure there is content and a valid thread id.
    if (isset($_POST['content'])
        && strlen($_POST['content']) > 0
        && isset($_POST['tid'])
        && $_POST['tid'] > 0)
    {
      $pid = $this->db->NewPost($_POST['tid'], $_POST['content'], $uid);
      $posts_per_page = $this->session->posts_per_page;
      $num_posts = $this->db->GetThreadNumPosts($_POST['tid']);
      $last_page = GetPageCount($num_posts, $posts_per_page);
      header("LOCATION: " . Pages::THREAD . "?tid={$_POST['tid']}&page=$last_page#post$pid");
    }
  }

  /* Preview new post */
  private function PreviewNewPost($uid)
  {
    // Contruct array for new post object and display it
    $post_info = array('pid'=>'0',
                       'tpid'=>'0',
                       'uid'=>"$uid",
                       'content'=>$_POST['content'],
                       'controls'=>'',
                       'time'=>'',
                       'edit'=>'',
                       'karma'=>array('plus_karma'=>'', 'minus_karma'=>''));
    $post = new Post($this->forum, $this->session, $this->db, $post_info);
    $post->Display();
  }

  /* Quote new post */
  private function QuotePost($pid, $post_uid)
  {
    $post = $this->db->GetPost($pid);
    $author = $this->db->GetUserName($post_uid, FALSE);
    P("[quote author=$author pid={$post['pid']} tpid={$post['tpid']}]{$post['content']}[/quote]\n");
  }

  /* Apply karma to a post */
  private function KarmaAction($uid, $action)
  {
    // Ensure poster and user are different.
    if ($_GET['puid'] == $uid)
    {
      echo "0";
    }
    else
    {
      $type = ($action === "karma_plus")? "plus" : "minus";
      if (TRUE == $this->db->AddPostKarma($type, $_GET['pid'], $_GET['puid'], $uid))
        echo UsrLink($uid, $this->session->GetUserName());
      else
        echo "0";
    }
  }

  /* Edit existing post */
  private function EditPost($pid, $post_uid, $action)
  {
    $post = $this->db->GetPost($pid);
    $this->tid = $post['tid'];
    $reply = array();

    // Submit case: take edit content and update post
    if ($action === 'edit_submit')
    {
      // Update post
      $post = $this->db->UpdatePost($_POST['content'], $_POST['pid']);
      // Update title if necessary
      if ($post['tpid'] == 1)
      {
        $title = $this->db->UpdateThreadTitle($this->tid, $_POST['title']);
        if ($title)
          $reply['title'] = $title;
      }

      $reply['content'] = PrepContent($post['content'], $this->tid);
      $reply['edit_time'] = 'edited ' . GetTime(TIME_FULL, $post['edit']);
      $reply['edit'] = ' ';
    }
    // Cancel case: echo back what's stored in DB for post content
    else if ($action === 'edit_cancel')
    {
      $reply['content'] == PrepContent($post['content'], $this->tid);
      if ($post['tpid'] == 1)
        $reply['title'] = $this->db->GetThreadTitle($this->tid);
      $reply['edit'] = ' ';
    }
    // Edit case: need to echo back pre-prepped content
    // TODO: leave the controls part to javascript?
    else if ($action === 'edit_edit')
    {
      $title_flag = 0;
      $content = $post['content'];
      if ($post['tpid'] == 1)
      {
        $title = $this->db->GetThreadTitle($this->tid);
        $reply['title'] = $title;
      }

      // Initiaing an edit session: create edit box
      $form = "<textarea class='edit_text' rows='10' cols='80' name='content' id='edit$pid'>$content</textarea>";
      if ($post['tpid'] == 1)
      {
        $form = "<input class='title' id='edit_title' type='text' name='title' maxlength='64' value='$title'>"
          . $form;
        $title_flag = 1;
      }

      $reply['content'] = PrepContent($post['content'], $this->tid);
      // Construct form with buttons.
      $reply['edit'] =
        Tag("form",
            $form,
            array('name'=>'edit'))
        . Button("submit", array('onclick'=>"editPost($pid, \"edit_submit\", $title_flag)"))
        . Button("preview", array('onclick'=>"editPost($pid, \"edit_preview\", $title_flag)"))
        . Button("cancel", array('onclick'=>"editPost($pid, \"edit_cancel\", $title_flag)"))
        . Tag('div', '', array('class'=>'edit_msg'));
    }
    else if ($action === 'edit_preview')
    {
      $reply['content'] = PrepContent($_POST['content'], $this->tid);
      if ($post['tpid'] == 1 && isset($_POST['title']))
        $reply['title'] = $_POST['title'];
    }
    else
      exit();

    echo json_encode($reply);
  }
}

$thread = new Thread($forum, $session, $db);
$thread->Display();
exit();
?>
