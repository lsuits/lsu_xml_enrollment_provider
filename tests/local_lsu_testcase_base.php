<?php

abstract class local_lsu_testcase_base extends advanced_testcase {
    
    protected $ues;
    
    protected static $datadir;
    
    // don't bother with course having id=1
    protected static $coursesSql = "SELECT * FROM {course} WHERE id NOT IN (1)";
    
    
    public function setup(){
        parent::setup();
        global $CFG, $DB;
        mtrace("running unit setup");
        self::$datadir   = $CFG->dirroot.DIRECTORY_SEPARATOR.'local/lsu/tests/enrollment_data/';
        static::$datadir = self::$datadir.static::$local_datadir;

        //init provider settings
        $this->configure_provider();
        
        //initialize enrol_ues_plugin for use throughout
        $this->ues = $this->create_and_configure_ues();
        $this->assertTrue(!empty($this->ues));

        $this->assertEquals(1, count($DB->get_records('course')));
        $this->assertEquals(0, count($DB->get_records('role_assignments')));
        $this->resetAfterTest();
    }
    
    private function create_and_configure_ues(){
        global $CFG, $DB;
        require_once $CFG->dirroot.DIRECTORY_SEPARATOR.'enrol/ues/lib.php';
        
        // config
        set_config('enrollment_provider', 'lsu', 'enrol_ues');
        set_config('email_report', 0, 'enrol_ues');
        
        /**
         * ues will email errors to admins no matter what the config values are
         * the admin user under PHPUnit has no email and moodlelib will throw a 
         * coding exception if not taken care of here.
         * 
         * Even fixed like this, expect the following output: 
         * Error: lib/moodlelib.php email_to_user(): Not sending email due to noemailever config setting
         */
        $admins = $DB->get_records('user', array('username'=>'admin'));
        $admin  = count($admins) == 1 ? array_shift($admins) : false;
        
        $admin->email = 'jpeak5@lsu.edu';
        $DB->update_record('user',$admin);
        
        
        return new enrol_ues_plugin();
    }
    
    private function configure_provider(){
        set_config('testing', 1, 'local_lsu');
        set_config('credential_location', 1, 'https://moodleftp.lsu.edu/credentials.php');
        set_config('student_data',0, 'local_lsu');
        set_config('sports_information',0, 'local_lsu');
        $this->initialize_wsdl();
    }
            
    
    public function initialize_wsdl(){
        global $CFG;
        
        $location = 'data.wsdl';
        set_config('wsdl_location', $location, 'local_lsu');

        file_put_contents($CFG->dataroot.DIRECTORY_SEPARATOR.$location, 'hello');
        $this->assertFileExists($CFG->dataroot.DIRECTORY_SEPARATOR.$location);
    }
    
    public function test_wsdl_exists(){
        global $CFG;
        $this->assertEquals('hello', file_get_contents($CFG->dataroot.DIRECTORY_SEPARATOR.get_config('local_lsu','wsdl_location')));
    }
    
    protected function set_datasource_for_stage($datapathSuffix){
        set_config('testdir', static::$datadir.$datapathSuffix, 'local_lsu');
        $datadir = get_config('local_lsu','testdir');
        $files = array('INSTRUCTORS', 'STUDENTS', 'SEMESTERS', 'COURSES');
        
        foreach($files as $file){
            $suspect = $datadir.'/'.$file;
            $this->assertFileExists($suspect);
        }
    }
    
    protected function getCourseIfExists($fullname){
        global $DB;
        return $DB->get_record('course', array('fullname' => $fullname));
    }
    
    /**
     * 
     * @param string $username
     * @param string $rolename
     * @param string $course
     */
    protected function userHasRoleInCourse($username, $rolename, $course) {
        global $DB;
        $user       = $DB->get_record('user',array('username'=>$username));
        if(!$user){
            throw new Exception('User does not exist');
        }

        $course     = $DB->get_record('course', array('fullname'=>$course));
        if(!$course){
            return false;
        }
        
        $context    = get_context_instance(CONTEXT_COURSE, $course->id);
        $role       = $DB->get_record('role', array('shortname'=>$rolename));
//        mtrace(sprintf("looking up role assignment for userid %d, roleid %d, contextid  %d, for course %s\n", $user->id, $role->id, $context->id, $course->fullname));
        
        $hasRole    = $DB->get_records(  //why does this return more than one record for a single class ?
                'role_assignments', 
                array(
                    'contextid'=>$context->id,
                    'roleid'=>$role->id,
                    'userid'=>$user->id,
                    )
                );

        return !empty($hasRole);
    }
    
    /**
     * Return all users with a given role in the given course
     * 
     * @param string $rolename shortname of the role
     * @param string $course course fullname
     */
    protected function usersWithRoleInCourse($rolename, $course) {
        global $DB;

        $course     = $DB->get_record('course', array('fullname'=>$course));
        if(!$course){
            return false;
        }

        $context    = get_context_instance(CONTEXT_COURSE, $course->id);

        mtrace(sprintf("getting contextid = %d for course fullname: %s", $context->id, $course->fullname));
        $role       = $DB->get_record('role', array('shortname'=>$rolename));

        return $DB->get_records(
                'role_assignments', 
                array(
                    'contextid'=>$context->id,
                    'roleid'=>$role->id,
                    )
                );
    }

}
?>
