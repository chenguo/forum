<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once("./include/common_cfg.php");
require_once ('src/sidebar.php');
require_once ('src/page.php');
require_once ('src/threadlist.php');

class Board extends Page
{
  private $page = 1;            // page number to display
  private $nthreads;            // threads per page to display
  private $nposts;              // posts per page (used to make links into threads)

  /* Constructor. */
  function Board ($forum, $session, $db)
  {
    if ( !($forum instanceof Forum) || !($session instanceof Session) )
      exit("Page instantiantion failed: bad forum or session object");

    // Set up class variables.
    $this->forum = $forum;
    $this->session = $session;
    $this->db = $db;
    $this->sidebar = new Sidebar($forum, $session);

    // Lists of files to include.
    $this->css = array(CSS::COMMON, CSS::BOARD, CSS::SIDEBAR);
    $this->js = array(JS::JQUERY, JS::COMMON, JS::SIDEBAR);

    // Display info.
    $this->nthreads = $this->session->threads_per_page;
    $this->nposts = $this->session->posts_per_page;
  }

  /* Actions taken on page request, before displaying page. */
  protected function LoadAction()
  {
    $ret = TRUE;

    // Make sure user is logged in.
    if ( !$this->session->CheckLogin(TRUE) || $this->session->GetUID() <= 0 )
    {
      header ("LOCATION: " . Pages::LOGIN);
      $ret = FALSE;
    }
    // Check for page requested
    else if ( isset($_REQUEST['page']) )
    {
      if ( ($page = intval($_REQUEST['page'])) < 1 )
        $page = 1;
    }
    return $ret;
  }

  /* Display body of board page. */
  protected function DisplayBody ()
  {
    PL(STag('body'));

    // Banner.
    PL( $this->Banner() );

    // Sidebar.
    $this->sidebar->Display();

    // Titlebar.
    $titlebar = $this->TitleBar();
    PL($titlebar);

    // Threads.
    $threads = $this->db->GetThreads($this->page, $this->nthreads);
    $threadlist = new ThreadList ($this->session, $this->db, $threads);
    $threadlist->Display($this->page);

    // TItlebar again.
    PL($titlebar);

    PL(STag('/body'));
  }

  /* Generate board titlebar. */
  private function TitleBar ()
  {
    // Links to pages of board.
    $plinks = MakePageLinks($this->page, $this->nthreads, $this->db->GetNumThreads(), Pages::BOARD);
    $pages = Div( $plinks, array('class'=>'brd_pages') );

    // Link to new thread page.
    $new_thr = Div ( hLink(Pages::MAKETHR, 'new thread'), array('class'=>'brd_new_thr') );
    return Div( $pages . $new_thr, array('class'=>'title_bar container') );
  }

}

$board = new Board($forum, $session, $db);
$board->Display();
exit;

?>
