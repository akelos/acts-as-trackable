<?php
class TrackableThingInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('trackable_things','id,name');
        $this->createTable('special_activities','id,
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
        $this->addIndex('special_activities','activity');
        $this->addIndex('special_activities','actor_class');
        $this->addIndex('special_activities','actor_identifier');
        $this->addIndex('special_activities','item_class');
        $this->addIndex('special_activities','item_identifier');
    }
    
    function down_1()
    {
        $this->dropTable('trackable_things');
        $this->dropTable('special_activities');
    }
}