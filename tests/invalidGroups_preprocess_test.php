<?php
global $CFG;
require_once 'local_xml_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';


/**
 * Orphaned groups  occur from time to time, 
 * likely the result of an interrupted cron run?
 * 
 * Tests the provider's ability to detect and remove invalid group memberships 
 * during its preprocess step
 */
class invalidGroups_preprocess_test extends local_xml_testcase_base {
    
    public static $local_datadir = 'invalidGroups_preprocess_test/';
    
    public function setup(){
        parent::setup();
        $this->run_cron_until_step(2);
    }

    /**
     * Ensure that our function to find Orphaned 
     * groups finds the correct number of them.
     */
    public function test_findOrphanedGroups(){
        $provider = $this->ues->provider();
        
        $this->assertEquals(0, count($provider->findOrphanedGroups()));

        $this->enrolFourUsersIntoInvalidGroup();
        
        $this->assertEquals(4, count($provider->findOrphanedGroups()));
    }

    /**
     * Ensure that the function to unenroll users 
     * unenrolls the correct number of users.
     */
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
        $provider->unenrollGroupsUsers($invalidUsers);
        
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
        // $group = $DB->get_record('groups', array('name'=>"TST2 2011 001", 'courseid'=>3));

        // second, empty group
        $group2 = $DB->get_record('groups', array('name'=>"TST2 2011 001", 'courseid'=>3));

        // enrol instance required for this action
        $instance = $this->ues->get_instance($group2->courseid);

        // get student role id
        $roleid = $DB->get_record('role', array('shortname'=>'student'))->id;
        
        // enroll students
        // @todo don't hardcode these
        foreach(array(5,8,30,24) as $id){
            global $DB;
            mtrace(sprintf("enrolling user %s as roleid %d into group id %d", $id, $roleid, $group2->id));
            $this->ues->enrol_user($instance, $id, $roleid);
            groups_add_member($group2->id, $id);
        }
    }
}