<?php

require_once dirname(__FILE__) . '/processors.php';

class lsu_enrollment_provider extends enrollment_provider {
    var $url;
    var $wsdl;
    var $username;
    var $password;

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

        list($username, $password) = explode("\n", $resp);

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
        // Clear student auditing flag on each run; It'll be set in processor
        // Clear graduating students
        return (
            ues_student::update_meta(array('student_audit' => 0)) and
            ues_user::update_meta(array('user_degree' => 0)) and
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

            if (empty($user->id)) {
                continue;
            }

            $cache[$data->idnumber] = $data;

            $user->save();
            events_trigger('ues_' . $name . '_updated', $user);
        }
    }
}
