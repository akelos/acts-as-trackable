<?php
class TrackableThing extends ActiveRecord
{
    var $acts_as = array('trackable'=>array('greet',
                                            'invite'=>
                                                 array('table_name'=>'special_activities',
                                                       'action'=>'[%activityTimestamp] %actor invited %item for %subject',
                                                       'destroy'=>false)));
}
?>