<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once("./include/defines.php");
require_once("./include/common_cfg.php");
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
      exit("Page instatiantion failed: bad object");

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
    // Handle post submission.
    else if ( isset($_REQUEST['action'])
              && $_REQUEST['action'] === 'post' )
    {
      $this->SubmitPost();
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
      $this->fav = makeLink("javascript:void(0)",
                      Img("/imgs/site/star_filled.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(0,$uid,$tid)")));
    }
    else
    {
      $this->fav = makeLink("javascript:void(0)",
                            Img("/imgs/site/star_empty.png", array('class'=>'favicon', 'onclick'=>"threadMarkFav(1,$uid,$tid)")));
    }


  }
}

$thread = new Thread($forum, $session, $db);
$thread->Display();
?>
