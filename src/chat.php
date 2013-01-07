<?php
/* Chat client. Will be used on most pages.
 */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);
require_once ('src/common_cfg.php');
require_once ('src/form.php');
require_once ('src/titledbox.php');

class Chat
{
  private $titledbox;

  public function Chat()
  {
    $this->titledbox = new TitledBox ('chat_box', TitledBox::NO_X, FALSE);
  }

  public function Display()
  {
    // Set chat title
    $this->titledbox->SetTitle ("Chat");

    // Set chat content and buttons.
    /* $form = new Form (array('class'=>'chat_input')); */
    /* $form->InsertInput(array('type'=>'text', 'size' =>  */

    $content = Div('', array('class'=>'chat_msg'))
      . Div(Tag('textarea', ''), array('class'=>'chat_input'));
    $this->titledbox->SetContent ($content);

    PL($this->titledbox->HTML());
  }
}


?>