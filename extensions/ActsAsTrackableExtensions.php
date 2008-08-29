<?php
/**
 * @ExtensionPoint BaseActiveRecord
 *
 */
class ActsAsTrackableExtensions
{
    function &track($activityName, &$item, $subject = null) {
        $return = false;
        if (isset($this->trackable) && method_exists($this->trackable,"track")) {
            $return=&$this->trackable->track(&$this,$activityName, &$item, $subject);
        }
        return $return;
    }
    
    function &trackAdvanced($activityName, &$actor, &$item, $subject = null) {
        $return = false;
        if (isset($this->trackable) && method_exists($this->trackable,"track")) {
            $return=&$this->trackable->track(&$actor,$activityName, &$item, $subject);
        }
        return $return;
    }
    
    function &get_activities($activityName, $options = array()) {
        $result = array();
        if (isset($this->trackable) && method_exists($this->trackable,"getActivities")) {
            $result = &$this->trackable->getActivities(&$this, $activityName, $options);
        }
        return $result;
    }
    
    function &get_activities_advanced(&$userIdentifier, $activityName, $options = array()) {
        $result = array();
        if (isset($this->trackable) && method_exists($this->trackable,"getActivities")) {
            $result = &$this->trackable->getActivities(&$userIdentifier, $activityName, $options);
        }
        return $result;
    }
}
?>