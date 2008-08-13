<?php

class ActsAsTrackablePlugin extends AkPlugin
{
    function load()
    {
        require_once($this->getPath().DS.'lib'.DS.'ActsAsTrackable.php');
    }
}

?>