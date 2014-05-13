<?php
global $CFG;
require_once 'local_xml_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';


/**
 * Group membership dupes occur from time to time, 
 * likely the result of an interrupted cron run?
 * 
 * Tests the provider's ability to detect and remove invalid group memberships 
 * during its preprocess step
 */
class dupesGroups_preprocess_test extends local_xml_testcase_base {
    
    public static $local_datadir = 'dupesGroups_preprocess_test/';
    
    public function setup(){
        parent::setup();
        $this->run_cron_until_step(2);
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
}