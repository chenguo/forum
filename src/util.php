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

// Parse text for forum display. This sets up img tags, emoticons, etc.
// TODO: repeatedly going through the post text must be pretty inefficient... Try to make the regex more efficient
// while still maintainable.
function PrepContent($content, $embed_vid=TRUE)
{
  global $db;
  global $session;

  $content = disableHTML($content);

  // [code] check.
  $content = preg_replace("/\[code\](.*?)\[\/code\]/is", "<code>$1</code>", $content);

  // [img] check.
  $content = preg_replace("/\[img\](.*?)\[\/img\]/i","<div class='img_container'><img src='$1' alt='[IMAGE]'></div>", $content);

  if ($embed_vid)
    {
      $embed_opts = "frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen";

      // Vimeo [vid] check.
      $content = preg_replace("/\[(vid|vimeo)\][^\]]*?vimeo.com\/(.*\/)*(\d*)\[\/\\1\]/i",
                              "<iframe src='http://player.vimeo.com/video/$3?title=0&amp;byline=0&amp;portrait=0' width='500' height='281' $embed_opts></iframe>",
                              $content);

      // Youtube url types
      // http://youtu.be/code
      // http://www.youtube.com/watch?v=code
      // http://www.youtube.com/v/code
      // http://www.youtube.com/embed/code

      // Youtube videos. Use create function for the seeking time resolution instead of anonymous, since host only has PHP 5.2
      $content = preg_replace_callback("/\[(vid|youtube)\].*?youtu(\.be\/|be.com\/(v\/|watch.*?v=))([0-9a-zA-Z_-]{8,})(&[^#\[]*)?(#t=(\d+m)?(\d+))?.*?\[\/\\1\]/",
                                       create_function('$match',
                                                       '$str = "";'
                                                       //. 'foreach ($match as $key => $val) { $str .= "$key::$val</br>"; }'
                                                       . '$time = 0;'
                                                       . 'if (isset($match[8])) $time = $match[8];'
                                                       . 'if (isset($match[7])) $time = $match[7] * 60 + $match[8];'
                                                       . '$str .= "<iframe class=\'youtube-player\' type=\'text/html\' width=\'640\' height=\'385\' '
                                                       . 'src=\'http://www.youtube.com/embed/$match[4]?rel=0&start=$time\' frameborder=\'0\' '
                                                       . 'webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";'
                                                       . 'return $str;'),
                                       $content);

      // MLB [vid] check.
      $content = preg_replace("/\[vid\][^\]]*?mlb.com.*?content_id=(\d+).*?\[\/vid\]/i",
                              "<iframe src='http://mlb.mlb.com/shared/video/embed/embed.html?content_id=$1&width=640&height=360&property=mlb' width='640' height='360' $embed_opts></iframe>",
                              $content);

      // Gametrailers [vid] embed.
      $content = preg_replace("/\[vid\][^\]]*?gametrailers.com.*?(\d+)(#.*?)?\[\/vid\]/i",
                              "<iframe src='http://media.mtvnservices.com/embed/mgid:moses:video:gametrailers.com:$1' width='512' height='288' $embed_opts></iframe>",
                              $content);
    } /* embed videos */

  // Audio
  $content = preg_replace("/\[aud\].*soundcloud.*(playlists|tracks)%2f(\w+).*?\[\/aud\]/i",
                          "<iframe src='http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2F$1%2F$2&show_artwork=true' width='100%' height=166' scrolling='no' frameborder='no'></iframe>",
                          $content);

  // [url] and other links check.
  $content = preg_replace_callback("/\[url=(.*?)\](.*?)\[\/url\]/",
                                   create_function('$match',
                                                   '$str = "";'
                                                   //. 'foreach ($match as $key => $val) { $str .= "$key::$val</br>"; }'
                                                   . 'if (preg_match("/.*\/\//", $match[1]) == FALSE)'
                                                   . '$match[1] = "http://" . $match[1];'
                                                   . '$str .= "<a href=\"$match[1]\">$match[2]</a>";'
                                                   . 'return $str;'),
                                   $content);
  $content = preg_replace_callbacK("/(\s|^|\])(([\w-:\/]+\.[\w-][\w-]+)([^\[\s]*)([^\[\s\.,!]))/",
                                   create_function('$match',
                                                   '$str = "";'
                                                   //. 'foreach ($match as $key => $val) { $str .= "$key::$val</br>"; }'
                                                   . '$link = $match[2];'
                                                   . 'if (preg_match("/.*\/\//", $link) == FALSE)'
                                                   . '$link = "http://" . $link;'
                                                   . '$str .= "$match[1]<a href=\"$link\">$match[2]</a>";'
                                                   . 'return $str;'),
                                   $content);

  // Newline.
  $content = preg_replace("/(\r)?\n/", "</br>", $content);
  // Useless whitespace around newlines.
  $content = preg_replace("/\s*<\/br>\s*/", "</br>", $content);

  // [quote] check.
  while (preg_match ("/\[quote\](.*?)\[\/quote\]/", $content) > 0)
    $content = preg_replace("/\[quote\](.*?)\[\/quote\](<\/br>)?/i",
                            "<div class='quote'>$1</div>",
                            $content, 1);

  while(preg_match ("/\[quote\s*author=(.*?)\s*pid=(\S*)\s*tpid=(\S*)\](.*?)\[\/quote\](\n)?/", $content, $matches) > 0)
    {
      $quote_pid = $matches[2];
      $post_meta = $db->GetPostMeta($quote_pid);
      $tid = $post_meta['tid'];
      $page = GetPageCount($matches[3], $session->posts_per_page);
      $link = hLink("thread.php?tid=$tid&page=$page#post$quote_pid" , $matches[1] . " wrote:");
      $content = preg_replace("/\[quote\s*author=(.*?)\s*pid=(\S*)\s*tpid=(\S*)\](.*?)\[\/quote\](<\/br>)?/i",
                            "<div class='quote'><b>$link</b></br>$4</div>",
                              $content, 1);
                              }
  while(preg_match ("/\[quote author=([^\]]*)\](.*)\[\/quote\]/", $content) > 0)
    $content = preg_replace("/\[quote author=([^\]]*)\](.*?)\[\/quote\](<\/br>)?/i",
                            "<div class='quote'><b>$1 wrote</b></br>$2</div>",
                            $content, 1);

  // [b|i|s|u] check
  $content = nestedTextTags($content);

  // [hid] check.
  $content = preg_replace("/\[hide\](.*?)\[\/hide\]/",
                          Tag("div",
                              Button("+", array('onclick'=>'expUnhide(this)', 'class'=>'button_exp')) . " Click to expand"
                              . Tag("div", "$1", array('class'=>'hidden'))
                              ,
                              array('class'=>'expandable')),
                          $content);

  return $content;
}

// Recursive function to replace [b|i|s|u] text codes
function nestedTextTags($input)
{
  $str = "";
  if (is_array($input))
    $str = "<$input[1]>" . $input[2] . "</$input[1]>";
  else
    $str = $input;
  return preg_replace_callback("/\[([bisu])\](.*?)\[\/\\1\]/i", 'nestedTextTags', $str);
}

?>
