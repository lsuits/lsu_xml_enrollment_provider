<?php

require_once 'local_xml_testcase_base.php';

class np1_p1_p2_test extends local_xml_testcase_base {

    static $local_datadir = 'np1_p1_p2/';

    public function test_step1(){
        global $DB;
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);
        
        //run cron against initial dataset
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // there should be one course
        $this->assertEquals(1, count($DB->get_records_sql(self::$coursesSql)));
        $inst1Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor one');
        $this->assertTrue((bool)$inst1Course);

        // inst1 as non-primary, no other teacher enrollments, 10 students, visible
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', $inst1Course->fullname));
        $this->assertEquals(1, count($this->usersWithRoleInCourse('teacher', $inst1Course->fullname)));
        $this->assertEquals(0, count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname)));
        $this->assertEquals(10, count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        $this->assertEquals(1, $inst1Course->visible);
        
        $this->endOfStep();
    }
    
    public function test_step2(){
        
        // step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // ----------------- STEP 2 ---------------------- //
        
        $this->set_datasource_for_stage(2);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // inst1 course should still exist, inst1 should now have both primary and non-primary roles
        // 10 students; course should be visible
        $inst1Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor one');

        $this->assertTrue((bool)$inst1Course);
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', $inst1Course->fullname));
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', $inst1Course->fullname));
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('teacher', $inst1Course->fullname)));
        $this->assertEquals(1, $inst1Course->visible);

        $this->endOfStep();
    }
    
    public function test_step3(){
        global $DB;
        
        // step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // step 2
        $this->set_datasource_for_stage(2);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        // step 3
        $this->set_datasource_for_stage(3);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        // now inst2 has all the students on only the editingteacher role
        // 10 students; course should be visible
        $inst2Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor two');

        $this->assertTrue((bool)$inst2Course);
        $this->assertTrue($this->userHasRoleInCourse('inst2', 'editingteacher', $inst2Course->fullname));
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', $inst2Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst2Course->fullname)));
        $this->assertEquals(1, $inst2Course->visible);

        // inst1 course should still exist, inst1 should still have both primary and non-primary roles
        // all students should have been unenrolled
        // course should be visible
        $inst1Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor one');

        $this->assertTrue((bool)$inst1Course);
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        
        /**
         * The rest of these fail. Known issue. Won't fix.
         * In the scenario where a non-primary, np1, of a course, c1 is promoted
         * to primary instructor, p1, of c1, AND THEN the course is re-assigned 
         * to some other primary instructor p2, ALL enrollments are dropped from 
         * the course c1, including both roles for the instructor (np1, p1).
         */
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'teacher', $inst1Course->fullname), "---------!!!!!!!!!!! Known issue; won't fix");
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', $inst1Course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('teacher', $inst1Course->fullname)));
        $this->assertEquals(0, $inst1Course->visible);
        
        $this->endOfStep();
    }
}
?>