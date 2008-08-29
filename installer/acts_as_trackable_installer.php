<?php


define('AK_AATR_PLUGIN_FILES_DIR', AK_APP_PLUGINS_DIR.DS.'acts_as_trackable'.DS.'installer'.DS.'files');


class ActsAsTrackableInstaller extends AkPluginInstaller
{

    function up_1()
    {
        $this->runMigration();
        echo "\n\nInstallation completed\n";
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
        echo "Uninstalling the acts_as_trackable plugin migration\n";
        $Installer->uninstall();
    }

}
?>