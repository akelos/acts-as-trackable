<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2007, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveRecord
 * @subpackage Behaviours
 * @author Arno Schneider <arno a.t. bermilabs dot com>
 * @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');
require_once(AK_APP_DIR.DS.'models'.DS.'activity.php');

/**
 * This plugin allows you to track any kind of activity related to your object.
 *
 * Tracking login activity into a backend:
 *
 * define the act in your model:
 * 
 * class User extends ActiveRecord
 * {
 *
 *     var $acts_as = array('trackable'=>array('login'=>
 *                        array('action'=>'User "%user" logged into "%item" from IP "%subject"')));
 *
 *     function get_tracking_description()
 *     {
 *         return $this->username;
 *     }
 * }
 *
 * and start tracking:
 *
 *    $activity = $user->track('login','backoffice',$this->Request->getIp());
 * 
 *    $activity->getDescription(); // User "administrator" logged into "backoffice" from IP "127.0.0.1"
 * 
 * == Retrieving Activities of a model
 * 
 * $activities = $user->get_activities('login'); // will retrieve all activities of type login, executed by $user
 * 
 * == Configuration Options
 * 
 * Configure your activities:
 * 
 * var $acts_as = array('trackable'=>array('activity1','activity2',...,'activityN'));
 * 
 * Each activity can have an option array:
 * 
 * var $acts_as = array('trackable'=>array('activity1'=>array(....)));
 * 
 * Option key / values are:
 * 
 * <tt>table_name</tt> - default: "activities" - Name of the table where the activities shall be stored
 * <tt>visibility</tt> - default: "public" - A filter column
 * <tt>destroy</tt> - default: "true" - If set to true, the activities of a model will be destroyed after the model is destroyed
 * 
 * <tt>action</tt> - default: "%actor executed activity "%actionName" on %item" - i18n representation of the description of the action
 *                   The following placeholders can be used:
 *                      | placeholder        | source column |
 *                      '%actor'            => $activity->actor_description,
                        '%item'             => $activity->item_description,
                        '%subject'          => $activity->subject,
                        '%actionName'       => $activity->activity,
                        '%activityTimestamp'=> $activity->created_at
                     
                     translations for the <tt>action</tt> description can be found in:

                        app/locales/_activity_translations/en.php
 * 
 */
class ActsAsTrackable extends AkObserver
{
    var $_instance;
    var $_taggableType;
    var $_tagList;
    var $_loaded = false;
    var $_cached_tag_column;
    var $_activities = array();

    function ActsAsTrackable(&$ActiveRecordInstance, $options = array())
    {
        $this->_instance = &$ActiveRecordInstance;
        $this->observe(&$this->_instance);
        $this->init($options);
    }

    function init($options = array())
    {
        require_once(AK_APP_DIR.DS.'models'.DS.'activity.php');
        $default_options = array('table_name'=>'activities',
                                 'visibility'=>'public',
                                 'action'=>'%actor executed activity "%actionName" on %item',
                                 'destroy'=>true);

        $parameters = array('available_options'=>array('table_name','visibility','action',
                                                       'visibility','destroy'));
        Ak::parseOptions($options, $default_options, $parameters,true);


        $this->_activities = $options;

    }
    function afterDestroy(&$record)
    {
        $activity = new Activity();
        foreach ($this->_activities as $activityName => $options) {
            if (isset($options['destroy']) && $options['destroy']==true) {

                $sql=sprintf('activity = %s AND actor_class = %s AND actor_identifier = %s',
                $record->_db->quote_string($activityName),
                $record->_db->quote_string($this->getClassificationFor($record)),
                $record->_db->quote_string($this->getIdentifierFor($record)));
                $activity->deleteAll($sql);

            }
        }
        return true;
    }
    function getClassificationFor(&$obj)
    {
        if (is_scalar($obj)) {
            return null;
        } else if (is_a($obj,'AkActiveRecord')) {
            return $obj->getModelName();
        } else {
            return strtolower(get_class($obj));
        }
    }
    function getIdentifierFor(&$obj)
    {
        if (isset($obj->id)) {
            return $obj->id;
        } else {
            return null;
        }
    }
    function getDescriptionFor(&$obj)
    {
        $description = '';
        if (is_scalar($obj)) {
            $description = $obj;
        } else if (method_exists($obj,'get_tracking_description')) {
            $description = $obj->get_tracking_description();
        } else if (isset($obj->id)) {
            $description = strtolower(get_class($obj)).'(#'.$obj->id.')';
        } else {
            $description = strtolower(get_class($obj)).'(anonymous)';
        }
        return $description;
    }
    function &track(&$record, $activityName, &$item, $subject = null)
    {
        $false = false;
        if (($res=$this->_validateActivityExists($activityName))) {
            $activityConfig = $this->_activities[$activityName];
            $activity = new Activity();
            $activity->setTableName($activityConfig['table_name']);
            $activity->set('activity',$activityName);
            $activity->set('actor_class',$this->getClassificationFor($record));
            $activity->set('actor_identifier',$this->getIdentifierFor($record));
            $activity->set('actor_description',$this->getDescriptionFor(&$record));
            $activity->set('item_class',$this->getClassificationFor($item));
            $activity->set('item_identifier',$this->getIdentifierFor($item));
            $activity->set('item_description',$this->getDescriptionFor(&$item));
            $activity->set('visibility',$activityConfig['visibility']);
            $activity->set('subject',$subject);
            $activity->set('action_description',$this->_activities[$activityName]['action']);
            $res = $activity->save();
            if ($res) {
                return $activity;
            }
        }
        return $false;
    }
    function _validateActivityExists($activityName, $triggerError=true)
    {
        if (isset($this->_activities[$activityName])) {
            return true;
        } else {
            if ($triggerError)trigger_error(Ak::t('Cannot track activity %action - No such action defined',array('%action'=>$activityName)));
            return false;
        }
    }
    function &getActivities(&$record, $activityName, $options = array())
    {
        $return = false;
        if (($res=$this->_validateActivityExists($activityName,false))) {
            $activity = new Activity();
            if (!is_array($options)) {
                $options = array();
            }
            if (!isset($options['select_prefix'])) {
                $options['select_prefix'] = '';
            }
            $columns = $activity->getColumns();

            $columns = array_keys($columns);
            $columns = array_diff($columns,array('action_description'));
            $select = join(',',$columns);
            $options['select_prefix']='SELECT '.$select.','.$record->_db->quote_string($this->_activities[$activityName]['action']).' AS action_description FROM '.$this->_activities[$activityName]['table_name'];
            if (!isset($options['conditions'])) {
                $options['conditions'] = '1=1';
            }
            $options['conditions'].=' AND actor_class = ? AND actor_identifier = ? AND activity = ?';
            if (!isset($options['bind'])) {
                $options['bind'] = array();
            }
            $options['bind'] = array_merge($options['bind'],array($this->getClassificationFor($record),
            $this->getIdentifierFor($record),
            $activityName));
            $activities = &$activity->find('all',$options);
             
            return $activities;
        }
        return $return;
    }
}
?>