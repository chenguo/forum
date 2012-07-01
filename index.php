<?php
ini_set('display_errors', 1); error_reporting( E_ALL | E_STRICT );
require_once("include/common_cfg.php");
require_once("src/page.php");

class Index extends Page
{
  function Index ($session)
  {
    $this->session = $session;
    $this->css = array(CSS::COMMON, CSS::INDEX);
    $this->js = array(JS::JQUERY, JS::INDEX);
  }

  protected function LoadAction()
  {
    $ret = true;
    // If user is logged in, immediately go to board.
    if ( $this->session->CheckLogin() )
    {
      header ("LOCATION: " . Pages::BOARD);
      $ret = FALSE;
    }
    // Attempt log in.
    if ( isset($_POST['username']) && isset($_POST['password']) )
    {
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

    // Username and password input
    $username = Div( STag('input', array('type'=>'text', 'size'=>'20', 'name'=>'username',
                                         'maxlength'=>'32')),
                     array('class'=>'field') );
    $password = Div( STag('input', array('type'=>'password', 'size'=>'20', 'name'=>'password',
                                         'maxlength'=>'32')),
                     array('class'=>'field') );
    $remember = Div( STag('input', array('type'=>'checkbox', 'name'=>'cookie', 'value'=>'set')),
                     array('class'=>'remember') );
    $remember_txt = Div('remember me', array('class'=>'remember'));
    $button = Div( Stag('input', array('type'=>'submit', 'value'=>'log in', 'class'=>'button')));
    $action = Stag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'login'));

    // Put the form together
    $form = Tag('form',
                $username . $password . $remember . $remember_txt . $button . $action,
                array('class'=>'login_form', 'action'=>Pages::LOGIN, 'method'=>'post'));

    // Put the body togethe
    $body = $banner . $form;
    PL( Tag('body', $body) );
  }

}

$index = new Index($session);
$index->Display();
?>
