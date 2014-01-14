<?php
global $CFG;
require_once 'local_lsu_testcase_base.php';
require_once $CFG->dirroot.'/enrol/ues/publiclib.php';

class cron_enrol_simple_replacement_test extends local_lsu_testcase_base {

    static $local_datadir = 'simple_replacement/';
    
    public function test_sanity(){
        $localdir = '/www/html/dev/local/lsu/tests/enrollment_data/simple_replacement/';
        $this->assertEquals(self::$datadir, $localdir);
        
        $this->set_datasource_for_stage(1);
        $this->assertEquals($localdir.'1', get_config('local_lsu', 'testdir'));

        
        $this->assertNotNull($this->ues->provider());
        
    }
    
    public function test_step1_initialEnrollment(){
        global $DB;
        
        // set test data files as input to the process
        $this->set_datasource_for_stage(1);
        
        //run cron against initial dataset
        $this->ues->cron();
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        
        // make assertions about enrollment as it should appear after step 1
        
        // there should be two courses
        $this->assertEquals(2, count($DB->get_records_sql(self::$coursesSql)));
        
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2010 for instructor three'));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2011 for instructor four'));
        
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', '2014 Spring TST2 2010 for instructor three'));
        $this->assertEquals(14,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2010 for instructor three')));
        
        $this->assertTrue($this->userHasRoleInCourse('isnt4', 'editingteacher', '2014 Spring TST2 2011 for instructor four'));
        $this->assertEquals(15,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2011 for instructor four')));
    }
    
    public function test_step2_swapWasDoneCorrectly() {
        global $DB;
        
        //run cron against initial dataset
        $this->set_datasource_for_stage(1);
        $this->ues->cron();
        
        //run cron against initial dataset
        $this->set_datasource_for_stage(2);
        $this->ues->cron();
        
        $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
        
        $this->assertEquals(3, count($DB->get_records_sql(self::$coursesSql)));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2010 for instructor three'));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2011 for instructor three'));
        $this->assertTrue((bool)$this->getCourseIfExists('2014 Spring TST2 2011 for instructor four'));
        
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', '2014 Spring TST2 2010 for instructor three'));
        $this->assertEquals(14,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2010 for instructor three')));
        
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', '2014 Spring TST2 2011 for instructor three'));
        $this->assertEquals(3,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2011 for instructor three')));
        
        $this->assertTrue($this->userHasRoleInCourse('isnt4', 'editingteacher', '2014 Spring TST2 2011 for instructor four'));
        $this->assertEquals(12,  count($this->usersWithRoleInCourse('student', '2014 Spring TST2 2011 for instructor four')));
    }
}

?>
