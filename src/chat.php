<?php
/* Chat client. Will be used on most pages.
 */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once ('src/common_cfg.php');
require_once ('src/form.php');
require_once ('src/titledbox.php');

class Chat
{
  private $titledbox;         // Container for chat box
  private $session;           // Session

  public function Chat($session)
  {
    $this->titledbox = new TitledBox ('chat_box', TitledBox::NO_X, FALSE);
    $this->session = $session;
  }

  public function Display()
  {
    // Set chat title
    $this->titledbox->SetTitle ("Chat");

    // Set chat content and buttons.
    // Embed uer name and uid into chat box. Approach was
    // tried with cookies, but this way is more robust
    // since cookies may be disabled.
    $user = $this->session->GetUserName();
    $uid = $this->session->GetUID();
    $content = Div('', array('id'=>'chat_msg',
                             'data-name'=>$user,
                             'data-uid'=>$user))
      . Div(Tag('textarea', ''), array('id'=>'chat_input'))
      . Div('0 users online', array('id'=>'chat_count'));
    $this->titledbox->SetContent ($content);

    PL($this->titledbox->HTML());
  }
}


?>