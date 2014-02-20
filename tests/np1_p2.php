<?php

require_once 'local_lsu_testcase_base.php';

class cron_enrol_initialNonPrimarySwappedOutForNewPrimary extends local_lsu_testcase_base {

    static $local_datadir = 'np1_p2/';

    public function test_step1_initialEnrollment(){
        global $DB;
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);
        
        //run cron against initial dataset
        $this->ues->cron();

        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // make assertions about enrollment as it should appear after step 1
        
        // there should be one course
        $this->assertEquals(1, count($DB->get_records_sql(self::$coursesSql)));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST1 1350 for instructor one'));
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', '2014 Spring TST1 1350 for instructor one'));
        
        $students = $this->usersWithRoleInCourse('student', '2014 Spring TST1 1350 for instructor one');
        $this->assertEquals(29, count($students));
        
        $this->endOfStep();
    }
    
    public function test_step2_swapOutNonPrimaryCreatesNewCourse(){
        global $DB;
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);

        //run cron against initial dataset
        $this->ues->cron();
        
        // ensure no errors
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // ensure there are 30 user enrollments
        $this->assertEquals(30, count($DB->get_records('user_enrolments')));
        
        $this->endOfStep();
        
        // ----------------- STAGE 2 ---------------------- //
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(2);
        
        // ensure there are no invalid groups
        $provider = $this->ues->provider();
        $this->assertEquals(0, count($provider->findOrphanedGroups()));
        
        //run cron against step 2 dataset
        $this->ues->cron();
        
        // ensure no errors
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // inst2 course should have been created
        $inst2Course = $this->getCourseIfExists('2014 Spring TST1 1350 for instructor two');
        
        $this->assertTrue((bool)$inst2Course);
        
        // ensure there are no invalid groups
        $this->assertEquals(0, count($provider->findOrphanedGroups()));
        
        // ensure there are 30 user enrollments
        $this->assertEquals(31, count($DB->get_records('user_enrolments')));

        // there should be two courses
        $this->assertEquals(2, count($DB->get_records_sql(self::$coursesSql)));

        
        // inst2 should be the teacher
        $this->assertTrue($this->userHasRoleInCourse('inst2', 'editingteacher', '2014 Spring TST1 1350 for instructor two'));
        
        // course should be visible
        $this->assertEquals(1, $inst2Course->visible);
        
        // student enrollment count
        $this->assertEquals(29, count($this->usersWithRoleInCourse('student', '2014 Spring TST1 1350 for instructor two')));
        
        // ------------------------------------- //

        // inst1 course should still exist
        $inst1Course = $this->getCourseIfExists('2014 Spring TST1 1350 for instructor one');
        $this->assertTrue((bool)$inst1Course);

        // inst1 should be the non-primary instructor, as before
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', '2014 Spring TST1 1350 for instructor one'));

        // the course should be invisible
        $this->assertEquals(0, $inst1Course->visible);
        
        // student enroillment should be 0
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('student', '2014 Spring TST1 1350 for instructor one')));
        
        $this->endOfStep();
        
        // ------------------------------------- //
    }
}

?>