<?php
function video_comments() {
  if ( comments_open() || get_comments_number() ) {
    comments_template('/comments-loggedin-only.php', true);
  }
}
