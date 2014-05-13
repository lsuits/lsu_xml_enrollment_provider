<?php

require_once 'local_xml_testcase_base.php';

class cron_enrol_initialNonPrimarySwappedOutForNewPrimary extends local_xml_testcase_base {

    static $local_datadir = 'np1_p2/';

    public function test_step1(){
        global $DB;
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);
        
        //run cron against initial dataset
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // there should be one course
        $this->assertEquals(1, count($DB->get_records_sql(self::$coursesSql)));
        $inst1Course = $this->getCourseIfExists('2014 Spring TST1 1350 for instructor one');
        $this->assertTrue((bool)$inst1Course);

        // inst1 as non-primary, no other teacher enrollments, 29 students, visible
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', $inst1Course->fullname));
        $this->assertEquals(1, count($this->usersWithRoleInCourse('teacher', $inst1Course->fullname)));
        $this->assertEquals(0, count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname)));
        $this->assertEquals(29, count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        $this->assertEquals(1, $inst1Course->visible);
        
        $this->endOfStep();
    }
    
    public function test_step2_swapOutNonPrimaryCreatesNewCourse(){
        global $DB;
        
        // step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // ----------------- STAGE 2 ---------------------- //
        
        $this->set_datasource_for_stage(2);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // inst2 course should have been created
        $inst2Course = $this->getCourseIfExists('2014 Spring TST1 1350 for instructor two');
        $this->assertTrue((bool)$inst2Course);

        // there should be two courses
        $this->assertEquals(2, count($DB->get_records_sql(self::$coursesSql)));

        
        // inst2 should be the editing teacher, course should be visible
        $this->assertTrue($this->userHasRoleInCourse('inst2', 'editingteacher', $inst2Course->fullname));
        $this->assertEquals(29, count($this->usersWithRoleInCourse('student', $inst2Course->fullname)));
        $this->assertEquals(1, count($this->usersWithRoleInCourse('editingteacher', $inst2Course->fullname)));
        $this->assertEquals(0, count($this->usersWithRoleInCourse('teacher', $inst2Course->fullname)));
        $this->assertEquals(1, $inst2Course->visible);

        // inst1 course should still exist, empty of students, with inst1 as the only teacher role, coursse should be invisible
        $inst1Course = $this->getCourseIfExists('2014 Spring TST1 1350 for instructor one');
        
        $this->assertTrue((bool)$inst1Course);
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', $inst1Course->fullname));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('teacher', $inst1Course->fullname)));
        $this->assertEquals(0, $inst1Course->visible);
        
        $this->endOfStep();
        
        // ------------------------------------- //
    }
}

?>