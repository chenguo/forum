<?php
require_once('src/html.php');

// Echo
function P ($str)
{
  echo $str;
}

// Echo str with newline
function PL ($str)
{
  echo "$str\n";
}

// Make page links. Format: << < p-2 p-1 page p+1 p+2 > >>
function MakePageLinks ($page, $nitems, $max_items, $link)
{
  $max_page = GetPageCount($max_items, $nitems);
  $page_links = "";

  if ( preg_match ("/\?.*?=.*/", $link) )
    $link .= "&";
  else if ( !preg_match ("/\?/", $link) )
    $link .= "?";

  // Only generate if there's multiple pages.
  if ($max_page > 1)
  {
    if ($page > 3)
      $page_links .= hLink($link . 'page=1', '<<', array('class'=>'page_link')) . "  ";
    if ($page > 2)
      $page_links .= hLink($link . 'page=' . ($page-2), $page - 2, array('class'=>'page_link')) . " ";
    if ($page > 1)
      $page_links .= hLink($link . 'page=' . ($page-1), $page - 1, array('class'=>'page_link')) . " ";
    $page_links .= "$page";
    if ($page < $max_page)
      $page_links .= " " . hLink($link . 'page=' . ($page+1), $page + 1, array('class'=>'page_link'));
    if ($page < $max_page - 1)
      $page_links .= " " . hLink($link . 'page=' . ($page+2), $page + 2, array('class'=>'page_link'));
    if ($page < $max_page - 2)
      $page_links .= "  " . hLink($link . 'page=' . $max_page, ">>", array('class'=>'page_link'));
  }
  return $page_links;
}

// Get the number of pages needed to display NITEMS at ITEMS_PER_PAGE.
function GetPageCount($max_items, $nitems)
{
  $max_pages = (($max_items % $nitems) == 0)? floor ($max_items / $nitems) : floor ($max_items / $nitems) + 1;
  return $max_pages;
}

// Get current time.
function GetTime($time_type = TIME_FULL, $time = 0)
{
  if ($time != 0)
      return date($time_type, strtotime($time));
  else
      return date($time_type);
}
?>
