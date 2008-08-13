<?php

class ActsAsTrackablePluginInstaller extends AkInstaller
{
    function down_1()
    {
        $this->dropTable('activities');
    }
    
     function up_1()
    {

        $this->createTable('activities','id,
                                        activity,
                                        actor_class,
                                        actor_identifier,
                                        actor_description,
                                        item_class,
                                        item_identifier,
                                        item_description,
                                        action_description,
                                        subject,
                                        visibility string(32),
                                        created_at');
        $this->addIndex('activities','activity');
        $this->addIndex('activities','actor_class');
        $this->addIndex('activities','actor_identifier');
        $this->addIndex('activities','item_class');
        $this->addIndex('activities','item_identifier');
    }
}
?>