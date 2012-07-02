<?php
ini_set('display_errors', 1); error_reporting(E_ALL | E_STRICT);

// Generate HTML tag pair
function Tag ($tag, $content, $opts = array())
{
  $options = '';
  foreach ($opts as $key=>$val)
    $options .= " $key='$val'";
  return "<$tag$options>$content</$tag>";
}

// Generate HTML tag
function STag ($tag, $opts = array())
{
  $options = '';
  foreach ($opts as $key=>$val)
    $options .= " $key='$val'";
  return "<$tag$options>";
}

// Generate div tag
function Div ($content, $opts = array())
{
  return Tag('div', $content, $opts);
}

// Generate HTML link
function hLink ($link, $desc, $opts = array())
{
  $options = '';
  foreach ($opts as $key=>$val)
    $options .= " $key='$val'";
  return "<a href='$link'$options>$desc</a>";
}

// Generate HTML link for user page
function UsrLink ($uid, $desc, $page = '')
{
  if ($page)
    return hLink(Pages::USER . "?uid=$uid&view=$page", $desc);
  else
    return hLink(Pages::USER . "?uid=$uid", $desc);
}

// Generate HTML to display user image
function Img($link, $opts = array())
{
  $options = "";
  if (count($opts) > 0)
    foreach ($opts as $optkey => $optval)
      $options .= " $optkey='$optval'";
  return "<img src='$link'$options>";
}

// Include a CSS file
function CSS ($files)
{
  $links = "";
  foreach ($files as $file)
    $links .= "<link href='$file' type='text/css' rel='stylesheet'>\n";
  return $links;
}

// Include a Javascript file
function JS ($files)
{
  $links = "";
  foreach ($files as $file)
    $links .= "<script type='text/javascript' src='$file'></script>\n";
  return $links;
}
?>