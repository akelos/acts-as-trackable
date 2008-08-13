<?php

class Activity extends ActiveRecord
{
    var $_action_description;
    var $_actor;
    var $_item;
    function beforeSave()
    {
        /**
         * make the action_description dynamic
         */
        unset($this->action_description);
        return true;
    }
    
    function &getActor()
    {
        $false = false;
        if (!isset($this->_actor)) {
            if (isset($this->actor_class) && isset($this->actor_identifier)) {
                if (!class_exists($this->actor_class)) {
                    @include_once(AkInflector::toModelFilename($this->actor_class));
                    if (!class_exists($this->actor_class)) return $false;
                }
                $this->_actor = new $this->actor_class($this->actor_identifier);
            } else {
                $this->_actor = $this->actor_description;
            }
        }
        
        return $this->_actor;
    }
    
    function &getItem()
    {
        $false = false;
        if (!isset($this->_item)) {
            if (isset($this->item_class) && isset($this->item_identifier)) {
                if (!class_exists($this->item_class)) {
                    @include_once(AkInflector::toModelFilename($this->item_class));
                    if (!class_exists($this->item_class)) return $false;
                }
                $this->_item = new $this->item_class($this->item_identifier);
            } else {
                $this->_item = $this->item_description;
            }
        }
        
        return $this->_item;
    }
    
    function setActionDescription($d)
    {
        $this->action_description = $d;
        $this->_action_description = $d;
    }
    function getDescription()
    {
        return Ak::t($this->_action_description,
                     array('%actor'=>$this->actor_description,
                           '%item'=>$this->item_description,
                           '%subject'=>$this->subject,
                           '%actionName'=>$this->activity,
                           '%activityTimestamp'=>$this->created_at),'_activity_translations');
    }
}