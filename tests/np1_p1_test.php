<?php

require_once 'local_xml_testcase_base.php';

class np1_p1 extends local_xml_testcase_base {

    static $local_datadir = 'np1_p1/';

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
        global $DB;
        
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
}

?>