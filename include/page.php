/*
 * Top level class for a web page. All page class inherit from this class.
 *
 * Defined functions
 * - Display(): display the page
 * - DisplayHead(): display HTML <head> portion
 * - DisplayBody(): display HTML <body> portion
 * - HTMLBegin(): display very beginning of HTML page
 * - HTMLEnd(): display very end of HTML page
 */

abstract class Page
{
  /* Constructor */
  function Page() { }

  /* Display the page. */
  final public function Display()
  {
    $this->HTMLBegin();
    $this->DisplayHead();
    $this->DisplayBody();
    $this->HTMLEnd();
  }

  /* Display HTML <head> */
  abstract protected function DisplayHead() { }

  /* Display HTML <body> */
  abstract protected function DisplayBody() { }

  /* Display very beginning of HTML page */
  final private function HTMLBegin()
  {
    echo "<!DOCTYPE HTML>\n<html>\n";
  }

  /* Display very end of HTML page */
  final private function HTMLEnd()
  {
    echo "</html>\n";
  }
}
