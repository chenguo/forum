<?php
require_once("./include/defines.php");


// Throw exception.
function throwException($message)
{
  throw new Exception("$message", null);
}

// Return an HTML link.
function makeLink($link, $desc, $class="")
{
  if (strcmp($class, "") != 0)
    $class = " class='$class'";
  return "<a href='$link'$class>$desc</a>";
}

/* Make link to a user page. */
function makeUserLink($uid, $name)
{
  return makeLink("user.php?uid=$uid", $name);
}

// Display HTML image.
function showImg($link, $options_array = array())
{
  $options = "";
  if (count($options_array) > 0)
    foreach ($options_array as $optkey => $optval)
      $options .= " $optkey='$optval'";
  return "<img src='$link'$options>";
}

// Make HTML tag.
function HTMLTag($tag, $value, $options_array = array())
{
  $options = "";
  if (count($options_array) > 0)
    foreach ($options_array as $optkey => $optval)
      $options .= " $optkey='$optval'";
  return "<$tag$options>$value</$tag>";
}

// Return a table.
function table($content, $options_array = array())
{
  return HTMLTag("table", $content, $options_array);
}

// Return HTML for column of a row in a table.
function tableCol($content, $options_array = array())
{
  return HTMLTag("td", $content, $options_array);
}

function tableRow($content, $options = "")
{
  if ($options == "")
    return "<tr>$content</tr>";
  return "<tr$options>$content</tr>";
}

// Return HTML for Set fontsize.
function fontSize($content, $fontsize)
{
  return "<font size='$fontsize'>$content</font>";
}

// Make a button.
function makeButton($text, $options_array = array())
{
  $options = "";
  if (count($options_array) > 0)
    foreach ($options_array as $optkey => $optval)
      $options .= " $optkey='$optval'";
  return "<input type='button' class='button' value='$text'$options>";
}

// Parse text for forum display. This sets up img tags, emoticons, etc.
function prepContent($content, $tid)
{
  // Disable HTML by replacing < and > with &#60 and &#62.
  $content = preg_replace("/</", "&#60", $content);
  $content = preg_replace("/>/", "&#62", $content);

  // [img] check.
  $content = preg_replace("/\[img\](.*?)\[\/img\]/i","<div class='img_container'><img src='$1' alt='[IMAGE]'></div>", $content);

  // [youtube] check.
  $content = preg_replace("/\[youtube\].*?youtube.*?v\/([0-9a-zA-Z_-]*).*?\[\/youtube\]/i",
                          "<iframe class='youtube-player' type='text/html' width='640' height='385' ".
                          "src='http://www.youtube.com/embed/$1' frameborder='0'></iframe>", $content);
  $content = preg_replace("/\[youtube\].*?youtube.*?v=([0-9a-zA-Z_-]*).*?\[\/youtube\]/i",
                          "<iframe class='youtube-player' type='text/html' width='640' height='385' ".
                          "src='http://www.youtube.com/embed/$1' frameborder='0'></iframe>", $content);

  // [vimeo] check.
  $content = preg_replace("/\[vimeo\].*?vimeo.com\/(\d*)\[\/vimeo\]/i",
                          "<iframe src='http://player.vimeo.com/video/$1?title=0&amp;byline=0&amp;portrait=0' width='400' height='225' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>",
                          $content);

  // Youtube [vid] check.
  $content = preg_replace("/\[vid\][^]]*?youtube.*?v\/([0-9a-zA-Z_-]*).*?\[\/vid\]/i",
                          "<iframe class='youtube-player' type='text/html' width='640' height='385' ".
                          "src='http://www.youtube.com/embed/$1' frameborder='0'></iframe>", $content);
  $content = preg_replace("/\[vid\][^\]]*?youtube.*?v=([0-9a-zA-Z_-]*).*?\[\/vid\]/i",
                          "<iframe class='youtube-player' type='text/html' width='640' height='385' ".
                          "src='http://www.youtube.com/embed/$1' frameborder='0'></iframe>",
                          $content);

  // Vimeo [vid] check.
  $content = preg_replace("/\[vid\][^\]]*?vimeo.com\/(\d*)\[\/vid\]/i",
                          "<iframe src='http://player.vimeo.com/video/$1?title=0&amp;byline=0&amp;portrait=0' width='400' height='225' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>",
                          $content);

  // MLB [vid] check.
  $content = preg_replace("/\[vid\][^\]]*?mlb.com.*?content_id=(\d+).*?\[\/vid\]/i",
                          "<iframe src='http://mlb.mlb.com/shared/video/embed/embed.html?content_id=$1&width=640&height=360&property=mlb' width='640' height='360' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>",
                          $content);

  // [url] and other links check.
  $content = preg_replace("/\[url=(.*?)\](.*?)\[\/url\]/",
                          "<a href=\"$1\">$2</a>", $content);
  $content = preg_replace("/(\s|^|\])([\w\.:\/]*([\w-][\w-]+\.[\w-][\w-]+)([^\[\s]*)([^\[\s\.,!]))/",
                          "$1<a href=\"$2\">$2</a>", $content);

  // Newline.
  $content = preg_replace("/(\r)?\n/", "</br>", $content);
  // Useless whitespace around newlines.
  $content = preg_replace("/\s*<\/br>\s*/", "</br>", $content);

  // [quote] check.
  while (preg_match ("/\[quote\](.*?)\[\/quote\]/", $content) > 0)
    $content = preg_replace("/\[quote\](.*?)\[\/quote\]/i",
                            "<div class='quote'>$1</div>",
                            $content, 1);

  while(preg_match ("/\[quote\s*author=(.*?)\s*pid=(\S*)\s*tpid=(\S*)\](.*?)\[\/quote\]/", $content, $matches) > 0)
    {
      $quote_pid = $matches[2];
      $page = GetPageCount($matches[3], DEFAULT_ITEMS_PER_PAGE);
      $link = makeLink("thread.php?tid=$tid&page=$page#post$quote_pid" , $matches[1] . " wrote:");
      $content = preg_replace("/\[quote\s*author=(.*?)\s*pid=(\S*)\s*tpid=(\S*)\](.*?)\[\/quote\]/i",
                            "<div class='quote'><b>$link</b></br>$4</div>",
                              $content, 1);
                              }
  while(preg_match ("/\[quote author=([^\]]*)\](.*)\[\/quote\]/", $content) > 0)
    $content = preg_replace("/\[quote author=([^\]]*)\](.*?)\[\/quote\]/i",
                            "<div class='quote'><b>$1 wrote</b></br>$2</div>",
                            $content, 1);

  // [s|u|b|i] check.
  $content = preg_replace("/\[b\](.*?)\[\/b\]/", "<b>$1</b>", $content);
  $content = preg_replace("/\[i\](.*?)\[\/i\]/", "<i>$1</i>", $content);
  $content = preg_replace("/\[s\](.*?)\[\/s\]/", "<s>$1</s>", $content);
  $content = preg_replace("/\[u\](.*?)\[\/u\]/", "<u>$1</u>", $content);

  return $content;
}

// Get the number of pages needed to display NITEMS at ITEMS_PER_PAGE.
function GetPageCount($nitems, $items_per_page)
{
  $max_pages = (($nitems % $items_per_page) == 0)? floor ($nitems / $items_per_page) : floor ($nitems / $items_per_page) + 1;
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
