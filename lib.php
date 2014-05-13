<?php

interface xml_semester_codes {
    const FALL = '1S';
    const SPRING = '2S';
    const SUMMER = '3S';
    const WINTER_INT = '1T';
    const SPRING_INT = '2T';
    const SUMMER_INT = '3T';
}

interface xml_institution_codes {
    const LSU_SEM = 'CLSB';
    const LAW_SEM = 'LAWB';

    const LSU_FINAL = 'CLSE';
    const LAW_FINAL = 'LAWE';

    const LSU_CAMPUS = '01';
    const LAW_CAMPUS = '08';

    const LSU_INST = '1590';
    const LAW_INST = '1595';
}

abstract class xml_source implements xml_institution_codes, xml_semester_codes {

    protected $xmldir;

    function __construct() {
        global $CFG;
        
        // get the enrollment dir and strip off leading or trailing slashes.
        $relDir = get_config('local_xml', 'xmldir');
        if(substr($relDir, 0, 1) == DIRECTORY_SEPARATOR){
            $relDir = substr($relDir, 1);
        }
        $len = strlen($relDir);
        if(substr($relDir, $len-1, $len) == DIRECTORY_SEPARATOR){
            $relDir = substr($relDir, 0, $len-1);
        }
        $this->xmldir   = $CFG->dataroot.DIRECTORY_SEPARATOR.$relDir.DIRECTORY_SEPARATOR;
    }

    protected function build_parameters(array $params) {
        return array ('parameters' => $params);
    }

    protected function escape_illegals($response) {
        $convertables = array(
            '/s?&s?/' => ' &amp; ',
        );
        foreach ($convertables as $pattern => $replaced) {
            $response = preg_replace($pattern, $replaced, $response);
        }
        return $response;
    }

    /**
     * @param type $response
     * @return type
     */
    protected function clean_response($response) {
        return $this->escape_illegals($response);
    }


    public function parse_date($date) {
        $parts = explode('-', $date);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }

    public function parse_name($fullname) {
        list($lastname, $fm) = explode(',', $fullname);
        $other = explode(' ', trim($fm));

        $first = $other[0];

        if (strlen($first) == 1) {
            $first = $first . ' ' . $other[1];
        }

        return array($first, $lastname);
    }

    /**
     * @todo make this dynamically configured through admin settings
     * @param type $semester_year
     * @param type $semester_name
     * @return type
     */
    public function encode_semester($semester_year, $semester_name) {

        $partial = function ($year, $name) {
            return sprintf('%d%s', $year, $name);
        };

        switch ($semester_name) {
            case 'Fall': return $partial($semester_year + 1, self::FALL);
            case 'WinterInt': return $partial($semester_year + 1, self::WINTER_INT);
            case 'Summer': return $partial($semester_year, self::SUMMER);
            case 'Spring': return $partial($semester_year, self::SPRING);
            case 'SummerInt': return $partial($semester_year, self::SUMMER_INT);
            case 'SpringInt': return $partial($semester_year, self::SPRING_INT);
        }
    }
}

abstract class xml_teacher_format extends xml_source {
    public function format_teacher($xml_teacher) {
        $primary_flag = trim($xml_teacher->PRIMARY_INSTRUCTOR);

        list($first, $last) = $this->parse_name($xml_teacher->INDIV_NAME);

        $teacher = new stdClass;

        $teacher->idnumber = (string) $xml_teacher->IDNUMBER;
        $teacher->primary_flag = (string) $primary_flag == 'Y' ? 1 : 0;

        $teacher->firstname = $first;
        $teacher->lastname = $last;
        $teacher->username = (string) $xml_teacher->PRIMARY_ACCESS_ID;

        return $teacher;
    }
}

abstract class xml_student_format extends xml_source {
    const AUDIT = 'AU';

    public function format_student($xml_student) {
        $student = new stdClass;

        $student->idnumber = (string) $xml_student->IDNUMBER;
        $student->credit_hours = (string) $xml_student->CREDIT_HRS;

        if (trim((string) $xml_student->GRADING_CODE) == self::AUDIT) {
            $student->student_audit = 1;
        }

        list($first, $last) = $this->parse_name($xml_student->INDIV_NAME);

        $student->username = (string) $xml_student->PRIMARY_ACCESS_ID;
        $student->firstname = $first;
        $student->lastname = $last;
        $student->user_ferpa = trim((string)$xml_student->WITHHOLD_DIR_FLG) == 'P' ? 1 : 0;

        return $student;
    }
}
