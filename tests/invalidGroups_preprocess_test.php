<?php
global $CFG;
require_once 'local_lsu_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';


/**
 * Orphaned groups occur from time to time, 
 * likely the result of an interrupted cron run.
 * 
 * Tests the provider's ability to detect and remove invalid group memberships 
 * during its preprocess step
 */
class invalidGroups_preprocess_test extends local_lsu_testcase_base {
    
    public static $local_datadir = 'invalidGroups_preprocess_test/';
    
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

    /**
     * for the case of duplicate group_memberships, make sure we can detect and
     * remove them. 
     * 
     * 1. Start with a clean state of enrollment where no duplicate group 
     * memberships exist
     * 
     * 2. create n duplicate group memberships
     * 
     * 3. ensure our dupe-detection function finds n records
     * 
     * 4. run the dupe-removal function and ensure that the total number of 
     * group members is what it was at the start of the test.
     * 
     * 5. ensure that the dupe-detector finds 0 dupes after the dupe-removal 
     * function runs
     * 
     * @global object $DB
     */
    public function testPreprocessFindsAndRemovesGroupDupes(){
        global $DB;
        $provider = $this->ues->provider();
        
        // Assumption: no group dupes exist in the test dataset
        $this->assertEquals(0, count($provider->findDuplicateGroupMembers()));
        $startCount = count($DB->get_records('groups_members'));
        
        // insert dupe records in the DB and make sure we can detect them
        $numDupes = 5;
        $this->createDuplicateGroupMembershipRecords($numDupes);
        $this->assertEquals($numDupes, count($provider->findDuplicateGroupMembers()));
        $this->assertEquals($startCount + $numDupes, count($DB->get_records('groups_members')));

        $provider->preprocess();

        // if this is true, then we have successfully eliminated the dupes
        $this->assertEquals(0, count($provider->findDuplicateGroupMembers()));
        
        // if this is true, then we have only eliminated dupes
        $this->assertEquals($startCount, count($DB->get_records('groups_members')));
    }
    
    public function test_detectStudentsInInvalidGroups(){
        $provider = $this->ues->provider();

        $this->assertEquals(0, count($provider->findOrphanedGroups()));
        
        $this->enrolFourUsersIntoInvalidGroup();
        
        $this->assertEquals(4, count($provider->findOrphanedGroups()));
    }
    
    private function createDuplicateGroupMembershipRecords($i){
        global $DB;
        $members    = $DB->get_records('groups_members');
        $startCount = count($members);
        $dupes      = array();

        while(count($dupes) < $i){
            $member = array_shift($members);

            $userName   = $DB->get_field('user', 'username', array('id'=>$member->userid));
            $courseid   = $DB->get_field('groups', 'courseid', array('id'=>$member->groupid));
            $courseName = $DB->get_field('course', 'fullname', array('id'=>$courseid));

            // only want to find duplicate students, not any instructor role of the course
            if($this->userHasRoleInCourse($userName, 'editingteacher', $courseName)){
                continue ;
            }

            unset($member->id);
            $DB->insert_record('groups_members', $member);
            $dupes[] = $member;
        }
        $this->assertEquals($i, count($dupes));
        $this->assertEquals($startCount + $i, count($DB->get_records('groups_members')));
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
        // @todo don't hardcode these
        foreach(array(5,8,30,24) as $id){
            $this->ues->enrol_user($instance, $id, $roleid);
            groups_add_member($group2->id, $id);
        }
    }
}