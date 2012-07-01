<?php

class ThreadList
{
  private $session;             // session object
  private $db;                  // database object
  private $threads;             // list of threads (mysql query result)

  /* Constructor. */
  function ThreadList ($session, $db, $threads)
  {
    if ( !($session instanceof Session) || !($db instanceof DB) )
      exit("ThreadList instantiantion failed: bad session or database object");

    $this->session = $session;
    $this->db = $db;
    $this->threads = $threads;
  }

  /* Display list of threads. */
  public function Display ($page)
  {
    PL( STag('div', array('class'=>'brd_threads container')) );

    // Threads header
    $this->DisplayHeader();

    $thread_list = '';
    foreach ($this->threads as $thread)
    {
      $this->DisplayThread ($thread, $page);
    }

    PL( Div('', array('class'=>'board_bottom')) );
    PL( STag('/div') );
  }

  /* Display threads list header */
  private function DisplayHeader()
  {
    PL( Div( Div('title', array('class'=>'brd_thr_title'))
             . Div('posts', array('class'=>'brd_thr_num'))
             . Div('views', array('class'=>'brd_thr_num'))
             . Div('created', array('class'=>'brd_thr_time'))
             . Div('last post', array('class'=>'brd_thr_time')) ),
        array('class'=>'brd_head') );
    PL( STag('hr') );
  }

  /* Display summary for a single thread. */
  private function DisplayThread($thread, $page)
  {
    $thread_info = $this->ThreadInfo($thread, $page);

    // Title column
    $thr_link = Div( $thread_info['link'], array('class'=>'brd_thr_link') );
    $page_links = Div( $thread_info['pages'],array('class'=>'brd_thr_page_links') );
    $thr_flags = Div( $thread_info['flags'], array('class'=>'brd_thr_flags') );
    $title = Div( $thr_link . $page_links . $thr_flags, array('class'=>'brd_thr_title') );

    // Post/View counts
    $posts = Div( $thread_info['posts'], array('class'=>'brd_thr_num') );
    $views = Div( $thread_info['views'], array('class'=>'brd_thr_num') );

    // Thread creator
    $creator = Div( Div($thread_info['creator'])
                    . Div($thread_info['create_time'], array('class'=>'time')),
                    array('class'=>'brd_thr_time') );

    // Last poster
    $poster = Div( Div($thread_info['last_poster'])
                    . Div($thread_info['post_time'], array('class'=>'time')),
                    array('class'=>'brd_thr_time') );

    // Print the thread summary
    PL( Div($title . $posts . $views . $creator . $poster,
            array('class'=>'brd_thread_row')) );
    PL( Stag('hr') );
  }

  /* Organize thread information for display. */
  private function ThreadInfo ($thread, $page)
  {
    $thread_info = array();
    $nposts = $this->session->posts_per_page;
    $link = Pages::THREAD . "?tid={$thread['tid']}";

    $thread_info['link'] = hLink($link, $thread['title'], array('class'=>'thread'));
    $thread_info['pages'] = MakePageLinks($page, $nposts, $thread['posts'], $link);
    $thread_info['flags'] = $this->ThreadFlags($thread, $link);
    $thread_info['create_time'] = GetTime(TIME_FULL, $thread['create_time']);
    $thread_info['post_time'] = GetTime(TIME_FULL, $thread['post_time']);
    $thread_info['posts'] = $thread['posts'];
    $thread_info['views'] = $thread['views'];
    $thread_info['creator'] = $this->db->GetUserName($thread['uid']);
    $thread_info['last_poster'] = $this->db->GetUserName($thread['last_uid']);

    return $thread_info;
  }

  /* For a particular user and thread, get notifications for user pertaining to that thread. */
  private function ThreadFlags ($thread, $link)
  {
    $uid = $this->session->GetUID();
    $flags = '';

    // Check the last post the user has viewed
    $last_view = $this->db->GetUserPostView($uid, $thread['tid']);
    $num_viewed = $last_view['tpid'];
    $nposts = $this->session->posts_per_page;
    $page = floor($num_viewed / $nposts) + 1;
    if ( $num_viewed < $thread['posts'] )
      $flags .= hLink($link . "&page=$page#post{$last_view['pid']}", 'new');
    return $flags;
  }

}