<?php
global $CFG;
require_once 'local_xml_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';

class p1_p2_test extends local_xml_testcase_base {

    static $local_datadir = 'p1_p2/';
    
    public function test_sanity(){
        $localdir = '/www/html/dev/local/xml/tests/enrollment_data/p1_p2/';
        $this->assertEquals(self::$datadir, $localdir);
        
        $this->currentStep = 1;
        $this->set_datasource_for_stage(1);
        $this->assertEquals($localdir.'1', get_config('local_xml', 'xmldir'));

        $this->assertNotNull($this->ues->provider());
        $this->endOfStep();
    }
    
    public function test_step1() {
        global $DB;

        $this->currentStep = 1;

        $this->assertEquals(0, count($DB->get_records('enrol', array('enrol'=>'ues'))));

        $this->assertEmpty($DB->get_records('enrol'));

        // set test data files as input to the process
        $this->set_datasource_for_stage(1);

        //run cron against initial dataset
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        
        // there should be one course
        $this->assertEquals(1, count($DB->get_records_sql(self::$coursesSql)));
        
        // this course should exist
        $inst1Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor one');
        $this->assertTrue((bool)$inst1Course);
        
        // this course should not exist yet
        $inst2Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor two');
        $this->assertFalse((bool)$inst2Course);

        // should be a single record in the {enrol} table for ues
        $this->assertEquals(1, count($DB->get_records('enrol', array('enrol'=>'ues', 'courseid' => $inst1Course->id))));

        // make assertions about enrollment as it should appear after step 1
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', $inst1Course->fullname));       // correct teacher
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst1Course->fullname))); // only one teacher
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));       // exactly 10 students
        $this->assertEquals(1, $inst1Course->visible);

        $this->endOfStep($this->currentStep);
    }
    
    public function test_step2() {
        global $DB;

        //run cron against initial dataset - step 1
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->endOfStep();
        
        //run cron - step 2
        $this->set_datasource_for_stage(2);
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        
        // should now be two courses
        $this->assertEquals(2, count($DB->get_records_sql(self::$coursesSql)));
        
        // this course should exist
        $inst1Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor one');
        $this->assertTrue((bool)$inst1Course);
        
        // this course should also now exist
        $inst2Course = $this->getCourseIfExists('2014 Spring TST2 2010 for instructor two');
        $this->assertTrue((bool)$inst2Course);


        // only one teacher, inst2, exactly 10 students, visible
        $this->assertTrue($this->userHasRoleInCourse('inst2', 'editingteacher', $inst2Course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst2Course->fullname))); // only one teacher
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', $inst2Course->fullname)));
        $this->assertEquals(1, $inst2Course->visible);

        // only one teacher, exactly 0 students, NOT visible
        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', $inst1Course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $inst2Course->fullname))); // only one teacher
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('student', $inst1Course->fullname)));
        $this->assertEquals(0, $inst1Course->visible);

        $this->endOfStep();
    }
}

?>