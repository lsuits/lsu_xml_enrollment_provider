<?php
global $CFG;
require_once 'local_lsu_testcase_base.php';

class np1p2_p1p2_sections_get_split_out_testcase extends local_lsu_testcase_base {
    
    // 002 has 6 students
    // 003 has 5
    // 004 has 2
    
    static $local_datadir = 'np1p2_p1p2_sections_get_split_out/';
    
    public function test_step1_section2(){
        
        global $DB;

        //run cron against initial dataset - step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // 2 courses should exist
        $inst4Course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor four');
        $this->assertTrue((bool)$inst4Course);

        // inst4 is primary; inst3 has non-primary assignment to sec 003, 6 + 5 students
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'teacher', $inst4Course->fullname));
        $this->assertTrue($this->userHasRoleInCourse('inst4', 'editingteacher', $inst4Course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst4Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('teacher', $inst4Course->fullname)));
        $this->assertEquals(11,  count($this->usersWithRoleInCourse('student', $inst4Course->fullname)));
        $this->assertEquals(1, $inst4Course->visible);
        
        // should have 2 sections
        $course4Groups = $this->getGroupsForCourse($inst4Course->id);
        $this->assertEquals(2, count($course4Groups));

        // 002 has 6 students + 1 instructor
        $sec2 = $this->getGroupByName('TST2 2011 002');
        $this->assertEquals(7, count($this->getGroupMembers($sec2->id)));
        
        // 003 has 5 students + 2 instructors
        $sec3 = $this->getGroupByName('TST2 2011 003');
        $this->assertEquals(7, count($this->getGroupMembers($sec3->id)));
    }

    public function test_step1_section4(){

        global $DB;

        //run cron against initial dataset - step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // 1 course should exist
        $inst3Course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor three');
        $this->assertTrue((bool)$inst3Course);

        // inst3 has primary assignment to sec 004, 2 students
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', $inst3Course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst3Course->fullname)));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('teacher', $inst3Course->fullname)));
        $this->assertEquals(2,  count($this->usersWithRoleInCourse('student', $inst3Course->fullname)));
        $this->assertEquals(1, $inst3Course->visible);
        
        // should have 1 section
        $course3Groups = $this->getGroupsForCourse($inst3Course->id);
        $this->assertEquals(1, count($course3Groups));

        // 004 has 2 students + 1 instructors
        $sec4 = $this->getGroupByName('TST2 2011 004');
        $this->assertEquals(3, count($this->getGroupMembers($sec4->id)));
    }
    
}