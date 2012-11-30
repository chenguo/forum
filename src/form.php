<?php

class Form
{
  private $attr = array();             // Form attributes.
  private $children = array();    // Elements of form.

  // Constructor.
  public function Form ($attr)
  {
    $this->attr = $attr;
  }

  // Insert input tag into form.
  public function InsertInput ($attr, $pos = -1)
  {
    if (is_array($attr))
    {
      $input = STag('input', $attr);
      $this->FormInsert($input, $pos);
    }
  }

  // Insert textarea into form.
  public function InsertTextarea ($attr, $pos = -1)
  {
    if (is_array($attr))
    {
      $textarea = Tag('textarea', '', $attr);
      $this->FormInsert($textarea, $pos);
    }
  }

  // Insert arbitrary markup into form (mostly used for text).
  public function InsertGeneric ($item, $pos = -1)
  {
    $this->FormInsert($item, $pos);
  }

  // Insert break into form.
  public function InsertBR ($pos = -1)
  {
    $this->FormInsert(BR(), $pos);
  }

  // Generate HTML for form.
  public function HTML ()
  {
    return Tag('form',
               implode("\n", $this->children),
               $this->attr);
  }

  private function FormInsert ($elem, $pos)
  {
    if ($pos < 0)
      array_push ($this->children, $elem);
    else
      array_splice ($this->children, $pos, 0, $elem);
  }

}
?>