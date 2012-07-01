<?php
/* Top level class for a web page. All page class inherit from this class.
 *
 * Defined functions
 * Final:
 * - Display(): display the page
 * - DisplayHead(): display HTML <head> portion
 * - DisplaySidebar(): display sidebar
 *
 * Implemented, overloadable
 * - Title(): generate title text
 * - Meta(): generate metadata
 * - Banner(): generate site banner
 *
 * Abstract
 * - LoadAction(): action to take on page load
 * - DisplayBody(): display HTML <body> portion
 */
ini_set('display_errors', 1); error_reporting( E_ALL | E_STRICT );

abstract class Page
{
  protected $forum;             // forum object
  protected $session;           // user session object
  protected $db;                // database object
  protected $sidebar;           // sidebar object
  protected $css = array();     // array of CSS files to load
  protected $js = array();      // array of Javascript files to load

  /* Constructor */
  function Page() { }

  /* Display the page. */
  final public function Display()
  {
    if ( $this->LoadAction() )
    {
      P(STag("!DOCTYPE html"));
      P(STag("html"));
      $this->DisplayHead();
      $this->DisplayBody();
      P(STag("/html"));
    }
  }

  /* Display HTML <head> */
  final private function DisplayHead()
  {
    // HTML head: CSS files, javascript files, metadata, and title.
    $head = CSS($this->css) . JS($this->js) . $this->Meta() . $this->Title();
    PL( Tag('head', $head) );
  }

  /* Generate page title */
  protected function Title()
  {
    return Tag('title', BOARD_NAME) . "\n";
  }

  /* Generate page metadata */
  protected function Meta()
  {
    return '';
  }

  /* Generate site banner */
  protected function Banner()
  {
    return Div(BOARD_NAME, array('class'=>'banner'));
  }

  /* Processing before beginning display, such as HTTP headers. */
  abstract protected function LoadAction();

  /* Display HTML <body> */
  abstract protected function DisplayBody();
}
?>