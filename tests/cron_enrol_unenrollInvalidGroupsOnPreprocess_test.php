<?php
global $CFG;
require_once 'local_lsu_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';

class cron_enrol_unenrollInvalidGroupsOnPreprocess_test extends local_lsu_testcase_base {
    
    public static $local_datadir = 'cron_enrol_unenrollInvalidGroupsOnPreprocess/';
    
    public function setup(){
        parent::setup();
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);
        
        //run cron against initial dataset
        $this->ues->cron();
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(2);
        
        //run cron against current dataset
        $this->ues->cron();
        
        $series = new stdClass();
        
    }
    
    public function test_findOrphanedGroups(){
        $provider = $this->ues->provider();
        $this->assertEquals(0, count($provider->findOrphanedGroups()));

        $this->enrolFourUsersIntoInvalidGroup();
        $this->assertEquals(4, count($provider->findOrphanedGroups()));
    }

    public function test_providerUnenrollOrphanedGroupsDuringPreprocess(){

        $provider = $this->ues->provider();
        
        // ensure we start with zero invlaid users
        $this->assertEquals(0, count($provider->findOrphanedGroups()));

        // add 4 invalid users
        $this->enrolFourUsersIntoInvalidGroup();

        // ensure we detect our invalid users
        $invalidUsers = $provider->findOrphanedGroups();
        $this->assertEquals(4, count($invalidUsers));

        // try to unenroll the invalid users
        $provider->unenrollOrphanedGroupsUsers($invalidUsers);
        
        // ensure that we have removed them
        $this->assertEquals(0, count($provider->findOrphanedGroups()));

    }
    
    public function test_detectStudentsInInvalidGroups(){
        $provider = $this->ues->provider();

        $this->assertEquals(0, count($provider->findOrphanedGroups()));
        
        $this->enrolFourUsersIntoInvalidGroup();
        
        $this->assertEquals(4, count($provider->findOrphanedGroups()));
    }
    
    private function enrolFourUsersIntoInvalidGroup(){
        global $DB;
        
        

        // first group 
        // we know this group exists in the test dataset, and contains users; do nothing
        //$group = $DB->get_record('groups', array('name'=>"TST2 2011 001", 'courseid'=>3));

        // second, empty group
        $group2 = $DB->get_record('groups', array('name'=>"TST2 2011 001", 'courseid'=>3));

        // enrol instance required for this action
        $instance = $this->ues->get_instance($group2->courseid);
        
        // get student role id
        $roleid = $DB->get_record('role', array('shortname'=>'student'))->id;
        
        // enroll students
        foreach(array(5,8,30,24) as $id){
            $this->ues->enrol_user($instance, $id, $roleid);
            groups_add_member($group2->id, $id);
        }
    }
}