<?php
ini_set('display_errors', 1); error_reporting( E_ALL | E_STRICT );
require_once ('include/common_cfg.php');
require_once ('src/page.php');

class Index extends Page
{
  /* Constructor. */
  function Index ($forum, $session)
  {
    if ( !($forum instanceof Forum) || !($session instanceof Session) )
      exit("Page instantiation failed: bad object");

    // Set up class variables
    $this->forum = $forum;
    $this->session = $session;

    // Lists of files to include
    $this->css = array(CSS::COMMON, CSS::INDEX);
    $this->js = array(JS::JQUERY, JS::COMMON, JS::INDEX);
  }

  /* Actions taken on page request, before displaying page. */
  protected function LoadAction()
  {
    $ret = TRUE;
    // If user is logged in, immediately go to board.
    if ( $this->session->CheckLogin() && $this->session->GetUID() > 0 )
    {
      header ("LOCATION: " . Pages::BOARD);
      $ret = FALSE;
    }
    // Attempt log in.
    else if ( isset($_REQUEST['action'])
              && $action === 'login'
              && isset($_POST['username'])
              && isset($_POST['password']) )
    {
      // Check for cookie
      $cookie = isset($_POST['cookie'])? TRUE : FALSE;
      if ( $this->session->Login($_POST['username'], $_POST['password'], $cookie) )
      {
        header ("LOCATION: " . Pages::BOARD);
        $ret = FALSE;
      }
    }
    return $ret;
  }

  /* Display body of login page. */
  protected function DisplayBody()
  {
    $banner = $this->Banner();

    // Username input
    $username = Div( STag('input',
                          array('type'=>'text', 'size'=>'20',
                                'name'=>'username', 'maxlength'=>'32')),
                     array('class'=>'field') );

    // Password input
    $password = Div( STag('input',
                          array('type'=>'password', 'size'=>'20',
                                'name'=>'password', 'maxlength'=>'32')),
                     array('class'=>'field') );

    // Login button
    $button = Div( Stag('input', array('type'=>'submit', 'value'=>'log in', 'class'=>'button')));

    // 'Remember Me' box and text
    $remember = Div( STag('input', array('type'=>'checkbox', 'name'=>'cookie', 'value'=>'set')),
                     array('class'=>'remember') );
    $remember_txt = Div('remember me', array('class'=>'remember'));

    // Login action
    $action = Stag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'login'));

    // Put the form together
    $form = Tag('form',
                $username . $password . $button . $remember . $remember_txt . $action,
                array('class'=>'login_form', 'action'=>Pages::LOGIN, 'method'=>'post'));

    // Put the body together
    $body = $banner . $form;
    PL( Tag('body', $body) );
  }

}

$index = new Index($forum, $session);
$index->Display();
?>
