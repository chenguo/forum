<?php
ini_set('display_errors', 1); error_reporting( E_ALL | E_STRICT );
require_once ('src/common_cfg.php');
require_once ('src/form.php');
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

    // Attempt log in.
    if ( isset($_REQUEST['action']) )
    {
      if ($_REQUEST['action']  === 'login'
          && isset($_POST['username'])
          && isset($_POST['password']) )
      {
        // Check for cookie
        $cookie = isset($_POST['cookie'])? TRUE : FALSE;
        if ( $this->session->Login($_POST['username'], $_POST['password'], $cookie) )
        {
          header ("LOCATION: " . Pages::BOARD);
        }
      }
      else if ($_REQUEST['action'] === 'logout')
      {
        $this->session->Logout();
        header("Location: ".Pages::LOGIN);
      }
      $ret = FALSE;
    }
    // If user is logged in, immediately go to board.
    else if ( $this->session->CheckLogin() && $this->session->GetUID() > 0 )
    {
      header ("LOCATION: " . Pages::BOARD);
      $ret = FALSE;
    }

    return $ret;
  }

  /* Display body of login page. */
  protected function DisplayBody()
  {
    $form = new Form (array('class'=>'login_form container',
                            'acion'=>Pages::LOGIN,
                            'method'=>'post'));
    $form->InsertInput (array('type'=>'text', 'size'=>'20',
                              'name'=>'username', 'maxlenght'=>'32',
                              'class'=>'field'));
    $form->InsertInput (array('type'=>'password', 'size'=>'20',
                              'name'=>'password', 'maxlength'=>'32',
                              'class'=>'field'));
    $form->InsertInput (array('type'=>'submit', 'value'=>'log in',
                              'class'=>'button'));
    $form->InsertGeneric (BR());
    $form->InsertInput (array('type'=>'checkbox', 'name'=>'cookie',
                              'value'=>'set', 'class'=>'remember'));
    $form->InsertGeneric ('Remember Me');
    $form->InsertInput (array('type'=>'hidden', 'name'=>'action',
                              'value'=>'login'));

    $input_box = Div($form->HTML(), array('class'=>'login_box'));

    // Put the body together
    $body = $this->Banner() . $input_box;
    PL( Tag('body', $body) );
  }

}

$index = new Index($forum, $session);
$index->Display();
?>
