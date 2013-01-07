<?php
/* UI overlay that appears as a box with a title and a optional
 * [X] button.
 */
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

class TitledBox
{
  /* Button style */
  const NO_X = 0;
  const XBUTTON = 1;
  const MINI_X = 2;

  private $id;            // ID.
  private $xbutton;       // Display [X] button. Only valid if ID is present.
  private $title;         // Title.
  private $title_class;   // Class of title.
  private $content;       // Content.
  private $padding;       // Whether to pad the box contents or not.

  /* Constructor */
  public function TitledBox ($id = '', $xbutton = NO_X,
                             $padding = TRUE, $title = '',
                             $content = '')
  {
    $this->id = $id;
    if ($id != '')
      $this->xbutton = $xbutton;
    $this->padding = $padding;
    $this->title = $title;
    $this->title_class = '';
    $this->content = $content;
  }

  /* Generate HTML for titled box */
  public function HTML ()
  {
    $title = "";
    // Add button if needed.
    if ($this->xbutton != TitledBox::NO_X)
    {
      $button_class = "";
      if ($this->xbutton == TitledBox::XBUTTON)
        $button_class = 'tbox_title_x';
      else if ($this->xbutton == TitledBox::MINI_X)
        $button_class = 'tbox_title_xmin';

      $title .= Button('X', array('class'=>$button_class,
                                  'onclick'=>"overlayHide(\"$this->id\")"));
    }
    $title_class = 'tbox_title_text';
    if ($this->title_class != "")
      $title_class .= " $this->title_class";
    $title .= Div($this->title, array('class'=>$title_class));

    $content_class = "tbox_content";
    if ($this->padding)
    {
      $content_class .= " tbox_content_pad";
    }
    return Div( Div($title, array('class'=>'tbox_title'))
                . Div($this->content, array('class'=>$content_class)),
                array('class'=>'titled_box',
                      'id'=>$this->id));
  }

  /* Set the title of the titled box. */
  public function SetTitle ($title, $class = "")
  {
    $this->title = $title;
    $this->title_class = $class;
  }

  /* Set the content of the titled box. */
  public function SetContent ($content)
  {
    $this->content = $content;
  }
}
?>
