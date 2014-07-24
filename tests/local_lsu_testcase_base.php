<?php
global $CFG;

//require_once $CFG->libdir.'/cronlib.php';


abstract class local_xml_testcase_base extends advanced_testcase {
    
    protected $ues;
    
    protected static $datadir;
    
    protected $currentStep;
    
    // don't bother with course having id=1
    protected static $coursesSql = "SELECT * FROM {course} WHERE id NOT IN (1)";
    
        public $studentRole;
        public $teacherRole;
        public $editingteacherRole;
    
    
    public function setup(){
        parent::setup();
        global $CFG, $DB;

        self::$datadir   = $CFG->dirroot.DIRECTORY_SEPARATOR.'local/xml/tests/enrollment_data/';
        static::$datadir = self::$datadir.static::$local_datadir;

        //init provider settings
        $this->configure_provider();
        
        //initialize enrol_ues_plugin for use throughout
        $this->ues = $this->create_and_configure_ues();
        $this->assertTrue(!empty($this->ues));

        $this->assertEquals(1, count($DB->get_records('course')));
        $this->assertEquals(0, count($DB->get_records('role_assignments')));
        
        foreach(array('students', 'teachers', 'courses', 'sections', 'semesters') as $table){
            $t = 'enrol_ues_'.$table;
            $this->assertEquals(0, count($DB->get_records($t)));
        }
        
        $provider = $this->ues->provider();
        $this->assertEquals(0, count($provider->findOrphanedGroups()));
        
        $this->assertEquals(0, count($DB->get_records('role_assignments')));
        $this->assertEquals(0, count($DB->get_records('groups')));
        $this->resetAfterTest();
        
        $this->init_roles();
        $this->currentStep = 0;
    }
    
    private function init_roles(){
        global $DB;
        $this->studentRole  = $DB->get_record('role', array('shortname'=>'student'));
        $this->teacherRole  = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->editingteacherRole  = $DB->get_record('role', array('shortname'=>'editingteacher'));
    }

    protected function create_and_configure_ues(){
        global $CFG, $DB;
        require_once $CFG->dirroot.DIRECTORY_SEPARATOR.'enrol/ues/lib.php';

        // config
        set_config('enrollment_provider', 'lsu', 'enrol_ues');
        set_config('email_report', 0, 'enrol_ues');

        // if we don't add UES to the CFG, we will fail to get an instance of the plugin from (enrol_get_plugins(<id>. true))
        // because it checks CFG for active plugins!!!
        $CFG->enrol_plugins_enabled .= ',ues';

        /**
         * ues will email errors to admins no matter what the config values are
         * the admin user under PHPUnit has no email and moodlelib will throw a 
         * coding exception if not taken care of here.
         * 
         * Even fixed like this, expect the following output: 
         * Error: lib/moodlelib.php email_to_user(): Not sending email due to noemailever config setting
         */
        $admin = $DB->get_record('user', array('username'=>'admin'));

        $admin->email = 'jpeak5@lsu.edu';
        $DB->update_record('user',$admin);
        
        $ues = enrol_get_plugin('ues');

        return $ues;
    }

    private function configure_provider(){
        set_config('testing', 1, 'local_xml');
        set_config('credential_location', 1, 'https://moodleftp.lsu.edu/credentials.php');
        set_config('student_data',0, 'local_xml');
        set_config('sports_information',0, 'local_xml');
        $this->initialize_wsdl();
    }

    public function initialize_wsdl(){
        global $CFG;
        
        $location = 'data.wsdl';
        set_config('wsdl_location', $location, 'local_xml');

        file_put_contents($CFG->dataroot.DIRECTORY_SEPARATOR.$location, 'hello');
        $this->assertFileExists($CFG->dataroot.DIRECTORY_SEPARATOR.$location);
    }
    
    public function test_wsdl_exists(){
        global $CFG;
        $this->assertEquals('hello', file_get_contents($CFG->dataroot.DIRECTORY_SEPARATOR.get_config('local_xml','wsdl_location')));
    }
    
    protected function set_datasource_for_stage($datapathSuffix){
        // @todo $dataPathSuffix may not necessarily be an int !!
        $this->currentStep = $datapathSuffix;
        
        set_config('xmldir', static::$datadir.$datapathSuffix, 'local_xml');
        $datadir = get_config('local_xml','xmldir');
        $files = array('INSTRUCTORS', 'STUDENTS', 'SEMESTERS', 'COURSES');
        
        foreach($files as $file){
            $suspect = $datadir.'/'.$file;
            $this->assertFileExists($suspect);
        }
        
        mtrace("");
        mtrace("||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||");
        mtrace(sprintf("||||||||||||||||||||||||| BEGIN STEP %s |||||||||||||||||||||||||", $this->currentStep));
    }
    
    protected function endOfStep(){
        // @todo $dataPathSuffix may not necessarily be an int !!
        mtrace(sprintf("||||||||||||||||||||||||| END   STEP %s |||||||||||||||||||||||||", $this->currentStep));
        mtrace("||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||");
        $this->currentStep++;
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
        
        $context    = context_course::instance($course->id);
        $role       = $DB->get_record('role', array('shortname'=>$rolename));
        
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
    
    protected function userHasRoleAnywhere($username, $rolename) {
        global $DB;
        $user       = $DB->get_record('user',array('username'=>$username));
        if(!$user){
            throw new Exception('User does not exist');
        }
        
        $role       = $DB->get_record('role', array('shortname'=>$rolename));
        
        $hasRole    = $DB->get_records(
                'role_assignments', 
                array(
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

        $context    = context_course::instance($course->id);
        $role       = $DB->get_record('role', array('shortname'=>$rolename));

        return $DB->get_records(
                'role_assignments', 
                array(
                    'contextid'=>$context->id,
                    'roleid'=>$role->id,
                    )
                );
    }
    
    public function getGroupsForCourse($courseid){
        global $DB;
        return $DB->get_records('groups', array('courseid'=>$courseid));
    }

    public function getGroupByNameInCourse($name, $courseid){
        global $DB;
        // many groups may have the same name; use get_records plural
        $groups = $DB->get_records('groups',array('name'=>$name, 'courseid'=>$courseid));
        if(count($groups) > 1){
            throw new Exception(sprintf('more than one group with name %s',$name));
        }else{
            return array_shift($groups);
        }
    }
    
    public function getGroupMembers($groupid){
        global $DB;
        return $DB->get_records('groups_members', array('groupid'=>$groupid));
    }
    
    public function group_has_member(stdClass $group, stdClass $user){
        
    }
    
    /**
     * 
     * @param string $role shortname of role
     * @param int $groupid
     * @param int $courseid
     * @param int $userid
     */
    public function user_has_group_role($role, $groupid, $courseid, $userid){
        global $DB;
        
        $members = groups_get_members_by_role($groupid, $courseid);
        $role  = $DB->get_record('role', array('shortname'=>$role));

        if(!empty($members[$role->id]->users[$userid])){
            return true;
        }else{
            if(!empty($members['*']->users[$userid])){
                $user = $members['*']->users[$userid];
                return !empty($user->roles[$roleid]);
            }else{
                return false;
            }
        }
    }
    
    public function count_groups_members_by_role(stdClass $role, stdClass $group){
        $members = groups_get_members_by_role($group->id, $group->courseid);
        if(!array_key_exists($role->id, $members)){
            return 0;
        }
        return count($members[$role->id]->users);
    }
    
    public function run_cron_until_step($x=1){
        $s = 1;
        while($s <= $x){
            $this->set_datasource_for_stage($s);
            $this->ues->cron();
            $this->assertEmpty($this->ues->errors, sprintf("UES finished with errors"));
            $s++;
        }
    }
}
?>
