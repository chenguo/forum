<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once ('src/common_cfg.php');
require_once ('src/form.php');
require_once ('src/sidebar.php');
require_once ('src/page.php');
require_once ('src/threadlist.php');
require_once ('src/titledbox.php');

class Board extends Page
{
  private $page = 1;            // page number to display
  private $nthreads;            // threads per page to display
  private $nposts;              // posts per page (used to make links into threads)
  private $uid;                 // User ID

  /* Constructor. */
  function Board ($forum, $session, $db)
  {
    if ( !($forum instanceof Forum) || !($session instanceof Session) )
      exit("Page instantiation failed: bad object");

    // Set up class variables.
    $this->forum = $forum;
    $this->session = $session;
    $this->db = $db;
    $this->sidebar = new Sidebar($forum, $session);

    // Lists of files to include.
    $this->css = array(CSS::COMMON, CSS::BOARD, CSS::SIDEBAR);
    $this->js = array(JS::JQUERY, JS::COMMON, JS::BOARD, JS::SIDEBAR);

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
    else
    {
      $this->uid = $this->session->GetUID();
    }

    // Check for page requested
    if ( isset($_REQUEST['page']) )
    {
      if ( intval($_REQUEST['page']) < 1 )
        $this->page = 1;
      else
        $this->page = $_REQUEST['page'];
    }

    if ( isset($_REQUEST['action']) )
    {
      $this->HandleAction();
      $ret = FALSE;
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

    // Overlay area.
    PL( $this->Overlay() );

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
    $new_thr = Div ( hLink("javascript:void(0)", 'new thread',
                           array('onclick'=>'newThread()')),
                     array('class'=>'brd_new_thr') );
    return Div( $pages . $new_thr, array('class'=>'title_bar container') );
  }

  /* Generate overlay area. */
  private function Overlay ()
  {
    $overlay = new TitledBox('newthr', TitledBox::XBUTTON);
    return Div($overlay->HTML(),
               array('class'=>'overlay', 'id'=>'newthr'));
  }

  /* Form for making new thread */
  private function NewThread ()
  {
    $form = new Form (array('class'=>'newthr_form', 'action'=>Pages::BOARD,
                            'method'=>'post'));
    $form->InsertInput (array('type'=>'text', 'name'=>'title',
                              'maxlength'=>'64',
                              'class'=>'newthr_title'));
    $form->InsertBR ();
    $form->InsertTextarea (array('rows'=>'10', 'cols'=>'80',
                                 'name'=>'content', 'class'=>'newthr_body'));
    $form->InsertBR ();
    $form->InsertInput (array('type'=>'submit', 'value'=>'create',
                              'class'=>'button'));
    $form->InsertInput (array('type'=>'hidden', 'name'=>'action',
                              'value'=>'newthrsubmit'));

    $title = "Make New Thread";
    return json_encode (array('title'=>$title, 'content'=>$form->HTML()));
  }

  /* Handle actions. */
  private function HandleAction()
  {
    $action = $_REQUEST['action'];
    if ($action === 'thrMarkFav'
        && isset($_POST['fav'])
        && isset($_POST['tid'])
        && intval($_POST['tid']) > 0)
    {
      // Failures will be exceptions, so assume this succeeds.
      $this->db->UpdateUserThrFav($this->uid, $_POST['tid'], $_POST['fav']);
      echo "1";
    }
    else if ($action === 'newthr')
    {
      echo $this->NewThread();
    }
    else if ($action === 'newthrsubmit'
             && isset($_POST['title']) && strlen($_POST['title']) > 0
             && isset($_POST['content']) && strlen($_POST['content']) > 0)
    {
      $tid = $this->forum->MakeThread($_POST['title'], $_POST['content'],
                                      $this->session->GetUID());
      if ($tid > 0)
        header ("LOCATION: thread.php?tid={$tid}");
      else
        header ("LOCATION: threads.php");
    }
  }
}

$board = new Board($forum, $session, $db);
$board->Display();
exit;

?>
