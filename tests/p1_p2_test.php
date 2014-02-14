<?php
global $CFG;
require_once 'local_lsu_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';

class p1_p2_test extends local_lsu_testcase_base {

    static $local_datadir = 'p1_p2/';
    
    public function test_sanity(){
        $localdir = '/www/html/dev/local/lsu/tests/enrollment_data/p1_p2/';
        $this->assertEquals(self::$datadir, $localdir);
        
        $this->currentStep = 1;
        $this->set_datasource_for_stage(1);
        $this->assertEquals($localdir.'1', get_config('local_lsu', 'testdir'));

        
        $this->assertNotNull($this->ues->provider());
        $this->endOfStep();
        
    }
    
    public function test_step1_initialEnrollment() {
        global $DB;

        $this->currentStep = 1;

        $this->assertEquals(0, count($DB->get_records('enrol', array('enrol'=>'ues'))));

        $this->assertEmpty($DB->get_records('enrol'));

        // set test data files as input to the process
        $this->set_datasource_for_stage(1);

        //run cron against initial dataset
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        $this->assertGreaterThan(0, count($DB->get_records('enrol', array('enrol'=>'ues'))));
        $this->assertEquals(1, count($DB->get_records('enrol', array('enrol'=>'ues', 'courseid' => 2))));

        // make assertions about enrollment as it should appear after step 1

        // there should be two courses
        $this->assertEquals(1, count($DB->get_records_sql(self::$coursesSql)));

        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2010 for instructor one'));
        $this->assertFalse((bool)$this->getCourseIfExists('2014 Spring TST2 2011 for instructor two'));

        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', '2014 Spring TST2 2010 for instructor one'));
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2010 for instructor one')));

        $this->endOfStep($this->currentStep);
    }
    
    public function test_step2_swapWasDoneCorrectly() {
        global $DB;

        // enrol instance debugging
        $this->assertEquals(0, count($DB->get_records('enrol', array('enrol'=>'ues', 'courseid'=>2))));
        
        //run cron against initial dataset
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        
        // enrol instance debugging
        $this->assertEquals(1, count($DB->get_records('enrol', array('enrol'=>'ues', 'courseid'=>2))));

        $this->endOfStep();
        
        //run cron against initial dataset
        $this->set_datasource_for_stage(2);
        $this->ues->cron();

        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));

        $this->assertEquals(2, count($DB->get_records_sql(self::$coursesSql)));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2010 for instructor one'));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2010 for instructor two'));

        $this->assertTrue($this->userHasRoleInCourse('inst2', 'editingteacher', '2014 Spring TST2 2010 for instructor two'));
        $this->assertEquals(10,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2010 for instructor two')));

        $this->assertTrue($this->userHasRoleInCourse('inst1', 'editingteacher', '2014 Spring TST2 2010 for instructor one'));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2010 for instructor one')));

        $this->endOfStep();
    }
}

?>
