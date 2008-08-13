<?php
require_once(AK_BASE_DIR.DS.'app'.DS.'vendor'.DS.'plugins'.DS.'acts_as_trackable'.DS.'lib'.DS.'ActsAsTrackable.php');

class ActsAsTrackableTest extends AkUnitTest
{

    function setUp()
    {
        $this->uninstallAndInstallMigration('ActsAsTrackablePlugin');
        $this->installAndIncludeModels('TrackableThing');
        $this->includeAndInstatiateModels('TrackableThing2');
        $this->trackable = new ActsAsTrackable(&$this->TrackableThing);
        $this->populateTables('trackable_things');
    }
    function test_get_classification_for()
    {
        $obj = 'test';
        $expectedClassification = null;
        $classification = $this->trackable->getClassificationFor($obj);
        $this->assertEqual($expectedClassification, $classification);
        
        $obj = new stdclass;
        $expectedClassification = 'stdclass';
        $classification = $this->trackable->getClassificationFor($obj);
        $this->assertEqual($expectedClassification, $classification);
        
        $obj = new TrackableThing();
        $expectedClassification = 'TrackableThing';
        $classification = $this->trackable->getClassificationFor($obj);
        $this->assertEqual($expectedClassification, $classification);
    }
    
    function test_get_identifier_for()
    {
        $obj = 'test';
        $expectedIdentifier = null;
        $identifier = $this->trackable->getIdentifierFor($obj);
        $this->assertEqual($expectedIdentifier, $identifier);
        
        $obj = new stdclass;
        $obj->id = 1;
        $expectedIdentifier = 1;
        $identifier = $this->trackable->getIdentifierFor($obj);
        $this->assertEqual($expectedIdentifier, $identifier);
        
        $obj = new stdclass;
        $expectedIdentifier = null;
        $identifier = $this->trackable->getIdentifierFor($obj);
        $this->assertEqual($expectedIdentifier, $identifier);
    }
    function test_get_description_for()
    {
        $expectedDescription = 'name';
        $description = $this->trackable->getDescriptionFor($expectedDescription);
        $this->assertEqual($expectedDescription, $description);
        
        $obj = new stdClass();
        $obj->id=1;
        $expectedDescription = 'stdclass(#1)';
        $description = $this->trackable->getDescriptionFor($obj);
        $this->assertEqual($expectedDescription, $description);
        
        $obj = new stdClass();
        $expectedDescription = 'stdclass(anonymous)';
        $description = $this->trackable->getDescriptionFor($obj);
        $this->assertEqual($expectedDescription, $description);
        
        $obj = new TrackableThing2();
        $obj->name = 'testname';
        $expectedDescription = 'testname';
        $description = $this->trackable->getDescriptionFor($obj);
        $this->assertEqual($expectedDescription, $description);
    }
    
    function test_register_activity_oneway()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $bermi->track('greet', $arno);
        $this->assertTrue($activity);
        $this->assertTrue($activity->id>0);
        $description = $activity->getDescription();
        $this->assertEqual('trackablething(#1) executed activity "greet" on trackablething(#2)',$description);
        
    }
    function test_get_actor()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $bermi->track('greet', $arno);
        
        $actor = &$activity->getActor();
        $this->assertEqual($bermi->id, $actor->id);
        
        $activity = $bermi->trackAdvanced('greet',$a='anonymous', $arno);
        
        $actor = &$activity->getActor();
        $this->assertEqual('anonymous', $actor);
        
    }
    
    function test_get_item()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $bermi->track('greet', $arno);
        
        $item = &$activity->getItem();
        $this->assertEqual($arno->id, $item->id);
        
        $activity = $bermi->trackAdvanced('greet',$a='anonymous', $i='item');
        
        $item = &$activity->getItem();
        $this->assertEqual('item', $item);
        
    }
    function test_register_activity_theotherway()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $arno->track('greet', $bermi);
        $this->assertTrue($activity);
        $this->assertTrue($activity->id>0);
        $description = $activity->getDescription();
        $this->assertEqual('trackablething(#2) executed activity "greet" on trackablething(#1)',$description);
        
    }
    function test_retrieve_activities()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $arno->track('greet', $bermi);
        
        $activities = $arno->get_activities('greet');
        $this->assertEqual(1,count($activities));
        
        $description = $activities[0]->getDescription();
        $this->assertEqual('trackablething(#2) executed activity "greet" on trackablething(#1)',$description);

        $activity = $arno->track('greet', $bermi);
        $activities = $arno->get_activities('greet');
        $this->assertEqual(2,count($activities));
    }
    function test_visibility()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $arno->track('greet', $bermi);

        $this->assertEqual('public',$activity->visibility);

        $bermi = new TrackableThing2(1);
        $this->assertTrue($bermi);
        $arno = new TrackableThing2(2);
        $this->assertTrue($arno);
        
        $activity = $bermi->track('login',$item='backoffice');
        $this->assertTrue($activity);
        $this->assertEqual('internal',$activity->visibility);
        
        $activities = $bermi->get_activities('login',array('conditions'=>'visibility="public"'));
        $this->assertFalse($activities);
        
        $activities = $bermi->get_activities('login',array('conditions'=>'visibility="internal"'));
        $this->assertTrue($activities);
        $this->assertEqual(1,count($activities));
        $this->assertEqual($activity->id,$activities[0]->id);
        
    }
    function test_retrieve_activities_with_options()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $jose = $this->TrackableThing->find(3);
        $this->assertTrue($jose);
        $activity = $arno->track('greet', $bermi);
        $activity = $bermi->track('greet', $arno);
        $activity = $bermi->track('greet', $jose);
        
        $activities = $arno->get_activities('greet');
        $this->assertTrue($activities);
        $this->assertEqual(1,count($activities));
        
        $activities = $bermi->get_activities('greet');
        $this->assertTrue($activities);
        $this->assertEqual(2,count($activities));
        
        $activities = $jose->get_activities('greet');
        $this->assertFalse($activities);
        
        $activities = $bermi->get_activities('greet',array('conditions'=>'item_identifier = '.$arno->id.' AND item_class="trackablething"'));
        $this->assertTrue($activities);
        $this->assertEqual(1,count($activities));
        $this->assertEqual(2, $activities[0]->item_identifier);
        
        
        $activities = $bermi->get_activities('greet',array('conditions'=>'item_identifier = '.$jose->id.' AND item_class="trackablething"'));
        $this->assertTrue($activities);
        $this->assertEqual(1,count($activities));
        $this->assertEqual(3, $activities[0]->item_identifier);
    }
    
    function test_retrieve_non_existing_activities()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $arno->track('greet', $bermi);
        
        $activities = $arno->get_activities('i do not exist');
        $this->assertFalse($activities);

    } 

    function test_track_non_existent_activity()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        
        $activity = $arno->track('i do not exist', $bermi);
        $this->assertError('Cannot track activity i do not exist - No such action defined');
        
    }
    
    function test_destroy_user_cascade_to_activity()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $arno->track('greet', $bermi);
        
        $activities = &$arno->get_activities('greet');
        $this->assertTrue($activities);
        $this->assertEqual(1,count($activities));
        
        $arno->destroy();
        
        $activities = &$arno->get_activities('greet');
        $this->assertFalse($activities);
    }
    
    function test_register_customized_activity()
    {
        $bermi = $this->TrackableThing->find(1);
        $this->assertTrue($bermi);
        $arno = $this->TrackableThing->find(2);
        $this->assertTrue($arno);
        $activity = $bermi->track('invite', $arno,'dinner');
        $this->assertTrue($activity);
        $this->assertTrue($activity->id>0);
        $description = $activity->getDescription();
        $this->assertTrue(preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] trackablething\(#1\) invited trackablething\(#2\) for dinner/',$description));
        
        $activity = new Activity();
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertFalse($found);
        
        $activity->setTableName('special_activities');
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertTrue($found);
    }
    
    function test_register_customized_activity_with_naming_method()
    {
        $bermi = new TrackableThing2(1);
        $this->assertTrue($bermi);
        $arno = new TrackableThing2(2);
        $this->assertTrue($arno);
        $activity = $bermi->track('invite', $arno,'dinner');
        $this->assertTrue($activity);
        $this->assertTrue($activity->id>0);
        $description = $activity->getDescription();
        $this->assertTrue(preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Bermi invited Arno for dinner/',$description));
        
        $activity = new Activity();
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertFalse($found);
        
        $activity->setTableName('special_activities');
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertTrue($found);
    }
    
    
    function test_register_anonymous_activity()
    {
        $bermi = new TrackableThing(1);
        $this->assertTrue($bermi);
        $arno = new TrackableThing(2);
        $this->assertTrue($arno);
        $activity = $bermi->trackAdvanced('invite',$actor='Someone',$item='Another guy','dinner');
        $this->assertTrue($activity);
        $this->assertTrue($activity->id>0);
        $description = $activity->getDescription();
        $this->assertTrue(preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] Someone invited Another guy for dinner/',$description));
        
        $activity = new Activity();
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertFalse($found);
        
        $activity->setTableName('special_activities');
        $found = $activity->findFirstBy('subject','dinner');
        $this->assertTrue($found);
    }
    
    
    
}
?>