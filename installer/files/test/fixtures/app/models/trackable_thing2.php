<?php
class TrackableThing2 extends ActiveRecord
{
    var $acts_as = array('trackable'=>array('greet',
                                            'invite'=>
                                                 array('table_name'=>'special_activities',
                                                       'action'=>'[%activityTimestamp] %actor invited %item for %subject',
                                                       'destroy'=>false),
                                            'login'=>array('visibility'=>'internal')));
                                                 
                                                 
    function TrackableThing2()
    {
        $this->setModelName("TrackableThing2");
        $attributes = (array)func_get_args();
        $this->setTableName('trackable_things', true, true);
        $this->init($attributes);
    }
    
    function get_tracking_description()
    {
        return $this->name;
    }
}
?>