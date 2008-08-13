<?php


define('AK_AATR_PLUGIN_FILES_DIR', AK_APP_PLUGINS_DIR.DS.'acts_as_trackable'.DS.'installer'.DS.'files');


class ActsAsTrackableInstaller extends AkInstaller
{
   
    var $_newModelMethods = array('&track'=>'
    function &track($activityName, &$item, $subject = null) {
        $return = false;
        if (isset($this->trackable) && method_exists($this->trackable,"track")) {
            $return=&$this->trackable->track(&$this,$activityName, &$item, $subject);
        }
        return $return;
    }
    ',
    '&trackAdvanced'=>'
    function &trackAdvanced($activityName, &$actor, &$item, $subject = null) {
        $return = false;
        if (isset($this->trackable) && method_exists($this->trackable,"track")) {
            $return=&$this->trackable->track(&$actor,$activityName, &$item, $subject);
        }
        return $return;
    }
    ',
    '&get_activities'=>'
    function &get_activities($activityName, $options = array()) {
        $result = array();
        if (isset($this->trackable) && method_exists($this->trackable,"getActivities")) {
            $result = &$this->trackable->getActivities(&$this, $activityName, $options);
        }
        return $result;
    }
    ',
    '&get_activities_advanced'=>'
    function &get_activities_advanced(&$userIdentifier, $activityName, $options = array()) {
        $result = array();
        if (isset($this->trackable) && method_exists($this->trackable,"getActivities")) {
            $result = &$this->trackable->getActivities(&$userIdentifier, $activityName, $options);
        }
        return $result;
    }
    ');

    
    function up_1()
    {
        $this->files = Ak::dir(AK_AATR_PLUGIN_FILES_DIR, array('recurse'=> true));
        empty($this->options['force']) ? $this->checkForCollisions($this->files) : null;
        $this->copyFiles();

        echo "\nAdding methods to shared_model.php.\n\n ";
        $this->addNewMethodsToSharedModel();
        $this->runMigration();
        echo "\n\nInstallation completed\n";
    }
    function addNewMethodsToSharedModel()
    {
        foreach ($this->_newModelMethods as $name=>$method) {
            echo "Adding method ActiveRecord::$name method: ";
            $res = $this->addMethodToSharedModel($name,$method);
            echo $res===true?'[OK]':'[FAIL]:'."\n-- ".$res;
            echo "\n";
        }
    }
    function removeNewMethodsFromSharedModel()
    {
        foreach ($this->_newModelMethods as $name=>$method) {
            $this->removeMethodFromSharedModel($name);
        }
    }
    function _addMethodToClass($class,$path,$name,$methodString)
    {
        $contents = Ak::file_get_contents($path);
        if (!preg_match('/function\s+'.$name.'/i',$contents) && !preg_match("|/\*\* AUTOMATED START: $name \*/|", $contents)) {
        
        return (Ak::file_put_contents($path, preg_replace('|class '.$class.'(.*?)\n.*?{|i',"class $class\\1
{
    /** AUTOMATED START: $name */
$methodString
    /** AUTOMATED END: $name */
",$contents))>0?true:'Could not write to '.$path);
        } else {
            return "Method $name already exists on $class in file $path.\n";
        }
    }
    function addMethodToSharedModel($name,$methodString)
    {
        $path = AK_APP_DIR.DS.'shared_model.php';
        return $this->_addMethodToClass('ActiveRecord',$path,$name,$methodString);
    }
    
    function addMethodToAppController($name,$methodString)
    {
        $path = AK_APP_DIR.DS.'application_controller.php';
        return $this->_addMethodToClass('ApplicationController',$path,$name,$methodString);
    }
    
    function _removeMethodFromClass($name,$path)
    {
        return Ak::file_put_contents($path, preg_replace("|(\n[^\n]*?/\*\* AUTOMATED START: $name \*/.*?/\*\* AUTOMATED END: $name \*/\n)|s","",Ak::file_get_contents($path)));
    }
    function removeMethodFromSharedModel($name)
    {
        $path = AK_APP_DIR.DS.'shared_model.php';
        return $this->_removeMethodFromClass($name,$path);
    }
    function removeMethodFromAppController($name)
    {
        $path = AK_APP_DIR.DS.'application_controller.php';
        return $this->_removeMethodFromClass($name,$path);
    }
    function copyFiles()
    {
        $this->_copyFiles($this->files);
    }
    function _copyFiles($directory_structure, $base_path = AK_AATR_PLUGIN_FILES_DIR)
    {
        foreach ($directory_structure as $k=>$node){
            $path = $base_path.DS.$node;
            if(is_dir($path)){
                echo 'Creating dir '.$path."\n";
                $this->_makeDir($path);
            }elseif(is_file($path)){
                echo 'Creating file '.$path."\n";
                $this->_copyFile($path);
            }elseif(is_array($node)){
                foreach ($node as $dir=>$items){
                    $path = $base_path.DS.$dir;
                    if(is_dir($path)){
                        echo 'Creating dir '.$path."\n";
                        $this->_makeDir($path);
                        $this->_copyFiles($items, $path);
                    }
                }
            }
        }
    }

    function _makeDir($path)
    {
        $dir = str_replace(AK_AATR_PLUGIN_FILES_DIR, AK_BASE_DIR,$path);
        if(!is_dir($dir)){
            mkdir($dir);
        }
    }

    function _copyFile($path)
    {
        $destination_file = str_replace(AK_AATR_PLUGIN_FILES_DIR, AK_BASE_DIR,$path);
        copy($path, $destination_file);
        $source_file_mode =  fileperms($path);
        $target_file_mode =  fileperms($destination_file);
        if($source_file_mode != $target_file_mode){
            chmod($destination_file,$source_file_mode);
        }
    }
    function checkForCollisions(&$directory_structure, $base_path = AK_AATR_PLUGIN_FILES_DIR)
    {
        foreach ($directory_structure as $k=>$node){
            if(!empty($this->skip_all)){
                return ;
            }
            $path = str_replace(AK_AATR_PLUGIN_FILES_DIR, AK_BASE_DIR, $base_path.DS.$node);
            if(is_file($path)){
                $message = Ak::t('File %file exists.', array('%file'=>$path));
                $user_response = AkInstaller::promptUserVar($message."\n d (overwrite mine), i (keep mine), a (abort), O (overwrite all), K (keep all)", 'i');
                if($user_response == 'i'){
                    unset($directory_structure[$k]);
                }    elseif($user_response == 'O'){
                    return false;
                }    elseif($user_response == 'K'){
                    $directory_structure = array();
                    return false;
                }elseif($user_response != 'd'){
                    echo "\nAborting\n";
                    exit;
                }
            }elseif(is_array($node)){
                foreach ($node as $dir=>$items){
                    $path = $base_path.DS.$dir;
                    if(is_dir($path)){
                        if($this->checkForCollisions($directory_structure[$k][$dir], $path) === false){
                            $this->skip_all = true;
                            return;
                        }
                    }
                }
            }
        }
    }
    function runMigration()
    {
        include_once(AK_APP_INSTALLERS_DIR.DS.'acts_as_trackable_plugin_installer.php');
        $Installer =& new ActsAsTrackablePluginInstaller();

        echo "Running the acts_as_trackable plugin migration\n";
        $Installer->install();
    }

    function down_1()
    {
        include_once(AK_APP_INSTALLERS_DIR.DS.'acts_as_trackable_plugin_installer.php');
        $Installer =& new ActsAsTrackablePluginInstaller();
        $this->removeNewMethodsFromSharedModel();
        echo "Uninstalling the acts_as_trackable plugin migration\n";
        $Installer->uninstall();
    }

}
?>