<?php
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
function Div($content, $opts = array())
{
  return Tag('div', $content, $opts);
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