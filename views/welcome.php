<!-- if USER SETUP == completed -->

<?php
if(!$has_session) {
    finalize('/');
}
?>

Welcome, <?=$user['nickname']?>!
<br>
<br>
Your account has been been created, and you can start collecting! If you wish to personalize your experience please, continue.



Choose your rating system:



Import your media from:
MyAnimeList
More...



Choose your time zone:



<!-- else -->