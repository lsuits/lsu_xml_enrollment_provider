<?php
global $CFG;
require_once dirname(__FILE__) . '/processors.php';
require_once $CFG->dirroot.'/enrol/ues/lib.php';

class lsu_enrollment_provider extends enrollment_provider {
    var $url;
    var $wsdl;
    var $username;
    var $password;

    var $testing;

    var $settings = array(
        'credential_location' => 'https://secure.web.lsu.edu/credentials.php',
        'wsdl_location' => 'webService.wsdl',
        'semester_source' => 'MOODLE_SEMESTERS',
        'course_source' => 'MOODLE_COURSES',
        'teacher_by_department' => 'MOODLE_INSTRUCTORS_BY_DEPT',
        'student_by_department' => 'MOODLE_STUDENTS_BY_DEPT',
        'teacher_source' => 'MOODLE_INSTRUCTORS',
        'student_source' => 'MOODLE_STUDENTS',
        'student_data_source' => 'MOODLE_STUDENT_DATA',
        'student_degree_source' => 'MOODLE_DEGREE_CANDIDATE',
        'student_anonymous_source' => 'MOODLE_LAW_ANON_NBR',
        'student_ath_source' => 'MOODLE_STUDENTS_ATH'
    );

    // User data caches to speed things up
    private $lsu_degree_cache = array();
    private $lsu_student_data_cache = array();
    private $lsu_sports_cache = array();
    private $lsu_anonymous_cache = array();

    function init() {
        global $CFG;

        $path = pathinfo($this->wsdl);

        // Path checks
        if (!file_exists($this->wsdl)) {
            throw new Exception('no_file');
        }

        if ($path['extension'] != 'wsdl') {
            throw new Exception('bad_file');
        }

        if (!preg_match('/^[http|https]/', $this->url)) {
            throw new Exception('bad_url');
        }

        require_once $CFG->libdir . '/filelib.php';

        $curl = new curl(array('cache' => true));
        $resp = $curl->post($this->url, array('credentials' => 'get'));

        list($username, $password) = $this->testing ? array('hello','world1') : explode("\n", $resp);

        if (empty($username) or empty($password)) {
            throw new Exception('bad_resp');
        }

        $this->username = trim($username);
        $this->password = trim($password);
    }

    function __construct($init_on_create = true) {
        global $CFG;

        $this->url = $this->get_setting('credential_location');

        $this->wsdl = $CFG->dataroot . '/'. $this->get_setting('wsdl_location');

        $this->testing = get_config('local_lsu', 'testing');

        if ($init_on_create) {
            $this->init();
        }
    }

    public function settings($settings) {
        parent::settings($settings);

        $key = $this->plugin_key();
        $_s = ues::gen_str($key);

        $optional_pulls = array (
            'student_data' => 1,
            'anonymous_numbers' => 0,
            'degree_candidates' => 0,
            'sports_information' => 1
        );

        foreach ($optional_pulls as $name => $default) {
            $settings->add(new admin_setting_configcheckbox($key . '/' . $name,
                $_s($name), $_s($name. '_desc'), $default)
            );
        }
    }

    public static function plugin_key() {
        return 'local_lsu';
    }

    function semester_source() {
        return new lsu_semesters(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('semester_source')
        );
    }

    function course_source() {
        return new lsu_courses(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('course_source')
        );
    }

    function teacher_source() {
        return new lsu_teachers(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('teacher_source')
        );
    }

    function student_source() {
        return new lsu_students(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_source')
        );
    }

    function student_data_source() {
        return new lsu_student_data(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_data_source')
        );
    }

    function anonymous_source() {
        return new lsu_anonymous(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_anonymous_source')
        );
    }

    function degree_source() {
        return new lsu_degree(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_degree_source')
        );
    }

    function sports_source() {
        return new lsu_sports(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_ath_source')
        );
    }

    function teacher_department_source() {
        return new lsu_teachers_by_department(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('teacher_by_department')
        );
    }

    function student_department_source() {
        return new lsu_students_by_department(
            $this->username, $this->password,
            $this->wsdl, $this->get_setting('student_by_department')
        );
    }

    function preprocess($enrol = null) {

        // cleanup orphaned groups- https://trello.com/c/lQqVUrpQ
        $orphanedGroupMemebers = $this-> findOrphanedGroups();
        $this->unenrollOrphanedGroupsUsers($orphanedGroupMemebers);

        // Clear student auditing flag on each run; It'll be set in processor
        return (
            ues_student::update_meta(array('student_audit' => 0)) and
            ues_user::update_meta(array('user_degree' => 0)) and
            // Safe to clear sports on preprocess now that end date is 21 days
            ues_user::update_meta(array('user_sport1' => '')) and
            ues_user::update_meta(array('user_sport2' => '')) and
            ues_user::update_meta(array('user_sport3' => '')) and
            ues_user::update_meta(array('user_sport4' => ''))
        );
    }

    function postprocess($enrol = null) {
        $semesters_in_session = ues_semester::in_session();

        $now = time();

        $attempts = array(
            'student_data' => $this->student_data_source(),
            'anonymous_numbers' => $this->anonymous_source(),
            'degree_candidates' => $this->degree_source(),
            'sports_information' => $this->sports_source()
        );

        foreach ($semesters_in_session as $semester) {

            foreach ($attempts as $key => $source) {
                if (!$this->get_setting($key)) {
                    continue;
                }

                if ($enrol) {
                    $enrol->log("Processing $key for $semester...");
                }

                try {
                    $this->process_data_source($source, $semester);
                } catch (Exception $e) {
                    $handler = new stdClass;

                    $handler->file = '/enrol/ues/plugins/lsu/errors.php';
                    $handler->function = array(
                        'lsu_provider_error_handlers',
                        'reprocess_' . $key
                    );

                    $params = array('semesterid' => $semester->id);

                    ues_error::custom($handler, $params)->save();
                }
            }
        }

        return true;
    }

    function process_data_source($source, $semester) {
        $datas = $source->student_data($semester);

        $name = get_class($source);

        $cache =& $this->{$name . '_cache'};
        foreach ($datas as $data) {
            $params = array('idnumber' => $data->idnumber);

            if (isset($cache[$data->idnumber])) {
                continue;
            }

            $user = ues_user::upgrade_and_get($data, $params);

            if(isset($data->user_college)) {
            $user->department = $data->user_college;
            }

            if (empty($user->id)) {
                continue;
            }

            $cache[$data->idnumber] = $data;

            $user->save();

            events_trigger('ues_' . $name . '_updated', $user);
        }
    }
    
    public function findOrphanedGroups() {
                global $DB;
        
        $sql = "SELECT
            CONCAT(u.id, '-', gg.id, '-', cc.id, '-', gg.name) as uid,
            u.id AS userId,
            cc.id AS courseId,
            gg.id as groupId,
            u.username,
            cc.fullname,
            gg.name
        FROM (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) AS gg
            INNER JOIN {course} cc ON cc.id = gg.courseid
            INNER JOIN {groups_members} ggm ON ggm.groupid = gg.id
            INNER JOIN {user} u ON ggm.userid = u.id
            INNER JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = 50
            INNER JOIN {role_assignments} ra ON ctx.id = ra.contextid AND u.id = ra.userid
            INNER JOIN {role} r ON ra.roleid = r.id AND r.archetype = 'student'
        WHERE CONCAT(gg.courseid,gg.name) NOT IN (
            SELECT DISTINCT(CONCAT(mc.id,g.name))
            FROM {enrol_ues_sections} s
                INNER JOIN {enrol_ues_courses} c ON s.courseid = c.id
                INNER JOIN {enrol_ues_semesters} sem ON s.semesterid = sem.id
                INNER JOIN {course} mc ON mc.idnumber = s.idnumber
                INNER JOIN 
                (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) g ON mc.id = g.courseid AND g.name = CONCAT(c.department, ' ', c.cou_number, ' ', s.sec_number)
            WHERE sem.name = 'Spring'
            AND sem.year = 2014)
        AND gg.name IN (
            SELECT DISTINCT(g.name)
            FROM {enrol_ues_sections} s
                INNER JOIN {enrol_ues_courses} c ON s.courseid = c.id
                INNER JOIN {enrol_ues_semesters} sem ON s.semesterid = sem.id
                INNER JOIN {course} mc ON mc.idnumber = s.idnumber
                INNER JOIN 
                (
            SELECT
                grp.id,
                grp.courseid,
                grp.name,
                c.fullname
            FROM (
                SELECT
                    g.name,
                    count(g.name) as gcount
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE c.fullname like '2014 Spring %'
                GROUP BY g.name
                HAVING gcount > 1
            ) AS dupes
            LEFT JOIN {groups} grp ON grp.name = dupes.name
            INNER JOIN {course} c ON c.id = grp.courseid
            WHERE c.fullname like '2014 Spring %'
                AND (
                        SELECT count(id) AS memcount
                        FROM {groups_members} 
                        WHERE groupid = grp.id
                    ) > 0
            ORDER BY c.fullname
            ) g ON mc.id = g.courseid AND g.name = CONCAT(c.department, ' ', c.cou_number, ' ', s.sec_number)
            WHERE sem.name = 'Spring'
            AND sem.year = 2014)
        AND cc.visible = 1
        AND cc.shortname LIKE '2014 Spring %';";
        
        return $DB->get_records_sql($sql);
    }

    /**
     * Specialized fn to unenroll orphaned groups
     * 
     * Wrapper around @see lsu_enrollment_provider::unenroll_users
     * Takes the output of @see lsu_enrollment_provider::findOrphanedGroups 
     * and prepares it for unenrollment.
     * 
     * @global object $DB
     * @param object[] $users rows from 
     * @see lsu_enrollment_provider::findOrphanedGroups
     */
    public function unenrollOrphanedGroupsUsers($users) {
        global $DB;

        $groups = array();
        foreach($users as $user){

            if(!isset($groups[$user->groupid])){
                $groups[$user->groupid] = array();
            }
            $dbuser = $DB->get_record('user', array('id' => $user->userid));
            $groups[$user->groupid][] = $dbuser;
        }

        foreach($groups as $groupId => $users){
            $group = $DB->get_record('groups', array('id'=>$groupId));
            $this->unenroll_users($group, $users, true);
        }
    }

    /**
     * 
     * @global object $DB
     * @param object $group row from db {groups}
     * @param object[] $users rows from {user}
     * @param boolean $removeInvalid switch to prevent adlding users back to group;
     * set to true if calling from a cleanup function like 
     * @see lsu_enrollment_provider::unenrollInvalidGroupsUsers
     */
    public function unenroll_users($group, $users, $removeInvalid = false) {
        global $DB;
        $ues        = new enrol_ues_plugin();
        $instance   = $ues->get_instance($group->courseid);
        $course     = $DB->get_record('course', array('id' => $group->courseid));

        foreach ($users as $user) {
            // Ignore pending statuses for users who have no role assignment
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            if (!is_enrolled($context, $user->id)) {
                continue;
            }

            groups_remove_member($group->id, $user->id);

            $roleid = $DB->get_field('role', 'id', array('shortname'=>'student'));
            if(!$removeInvalid){
                $ues->unenrol_user($instance, $user->id, $roleid);
                groups_add_member($group->id, $user->id);
            }
        }

        $count_params = array('groupid' => $group->id);

        if (!$DB->count_records('groups_members', $count_params)) {
            // Going ahead and delete as delete
            groups_delete_group($group);
        }
    }
}