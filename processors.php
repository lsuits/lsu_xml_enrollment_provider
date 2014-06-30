<?php

require_once dirname(__FILE__) . '/lib.php';

class xml_semesters extends xml_source implements semester_processor {

    function semesters($date_threshold) {

        // TODO: this is not used, but it should be...
        if (is_numeric($date_threshold)) {
            $date_threshold = ues::format_time($date_threshold);
        }

        $response      = file_get_contents($this->xmldir.'SEMESTERS.xml');
        $xml_semesters = new SimpleXmlElement($this->clean_response($response));

        $semesters = array();

        foreach($xml_semesters->ROW as $xml_semester) {

            $semester = new stdClass;
            $semester->year = (string) $xml_semester->YEAR;
            $semester->name = (string) $xml_semester->NAME;
            $semester->campus = (string) $xml_semester->CAMPUS;
            $semester->session_key = (string) $xml_semester->SESSION_KEY;
            $semester->classes_start = (string) $xml_semester->CLASSES_START;
            $semester->grades_due = (string) $xml_semester->GRADES_DUE;
            $semesters[] = $semester;
        }

        return $semesters;
    }
}

class xml_courses extends xml_source implements course_processor {

    function courses($semester) {

        $courses = array();

        $response    = file_get_contents($this->xmldir.'COURSES.xml');
        $xml_courses = new SimpleXmlElement($this->clean_response($response));

        foreach ($xml_courses->ROW as $xml_course) {
            $department = (string) $xml_course->DEPT_CODE;
            $course_number = (string) $xml_course->COURSE_NBR;

            // @todo this is LSU-specific
            $law_not = ($semester->campus == 'LAW' and $department != 'LAW');
            $lsu_not = ($semester->campus == 'LSU' and $department == 'LAW');

            // Course is not semester applicable
            if ($law_not or $lsu_not) {
                continue;
            }

            // @todo this may never get called, considering the conditional below.
            $is_unique = function ($course) use ($department, $course_number) {
                return ($course->department != $department or
                    $course->cou_number != $course_number);
            };

            // @todo why is this checking the emptiness of an uninitialized var ?
            if (empty($course) or $is_unique($course)) {
                $course = new stdClass;
                $course->department = $department;
                $course->cou_number = $course_number;
                $course->course_type = (string) $xml_course->CLASS_TYPE;
                $course->course_first_year = (int) $xml_course->COURSE_NBR < 5200 ? 1 : 0;

                $course->fullname = (string) $xml_course->COURSE_TITLE;
                $course->course_grade_type = (string) $xml_course->GRADE_SYSTEM_CODE;

                $course->sections = array();

                $courses[] = $course;
            }

            $section = new stdClass;
            $section->sec_number = (string) $xml_course->SECTION_NBR;

            $course->sections[] = $section;
        }

        return $courses;
    }
}

class xml_teachers_by_department extends xml_teacher_format implements teacher_by_department {

    function teachers($semester, $department) {

        $teachers = array();

        $response     = file_get_contents($this->xmldir.'INSTRUCTORS.xml');
        $xml_teachers = new SimpleXmlElement($this->clean_response($response));

        foreach ($xml_teachers->ROW as $xml_teacher) {
            $teacher = $this->format_teacher($xml_teacher);

            // Section information
            $teacher->department = $department;
            $teacher->cou_number = (string) $xml_teacher->CLASS_COURSE_NBR;
            $teacher->sec_number = (string) $xml_teacher->SECTION_NBR;

            $teachers[] = $teacher;
        }

        return $teachers;
    }
}

class xml_students_by_department extends xml_student_format implements student_by_department {

    function students($semester, $department) {

        $response = file_get_contents($this->xmldir.'STUDENTS.xml');
        $xml_students = new SimpleXmlElement($this->clean_response($response));

        $students = array();
        foreach ($xml_students->ROW as $xml_student) {

            $student = $this->format_student($xml_student);

            // Section information
            $student->department = $department;
            $student->cou_number = (string) $xml_student->COURSE_NBR;
            $student->sec_number = (string) $xml_student->SECTION_NBR;

            $students[] = $student;
        }

        return $students;
    }
}

/**
 * @todo not currently used, but available through UES; will cause an error if called
 */
class xml_teachers extends xml_teacher_format implements teacher_processor {

    function teachers($semester, $course, $section) {
        $semester_term = $this->encode_semester($semester->year, $semester->name);

        $teachers = array();

        // @TODO: take these params into account so that reprocess will work.
        $params = array($course->cou_number, $semester->session_key,
            $section->sec_number, $course->department, $semester_term, $campus);

        $response     = file_get_contents($this->xmldir.'INSTRUCTORS.xml');
        $xml_teachers = new SimpleXmlElement($this->clean_response($response));

        foreach ($xml_teachers->ROW as $xml_teacher) {

            $teachers[] = $this->format_teacher($xml_teacher);
        }

        return $teachers;
    }
}

/**
 * @todo not currently used, but available through UES; will cause an error if called
 */
class xml_students extends xml_student_format implements student_processor {

    function students($semester, $course, $section) {
        $semester_term = $this->encode_semester($semester->year, $semester->name);

        $campus = $semester->campus == 'LSU' ? self::LSU_CAMPUS : self::LAW_CAMPUS;

        $params = array($campus, $semester_term, $course->department,
            $course->cou_number, $section->sec_number, $semester->session_key);

        $xml_students = $this->invoke($params);

        $students = array();
        foreach ($xml_students->ROW as $xml_student) {

            $students[] = $this->format_student($xml_student);
        }

        return $students;
    }
}

class xml_student_data extends xml_source {

    function student_data($semester) {

        $response = file_get_contents($this->xmldir.'STUDENT_DATA.xml');
        $xml_data = new SimpleXmlElement($this->clean_response($response));


        $student_data = array();

        foreach ($xml_data->ROW as $xml_student_data) {
            $stud_data = new stdClass;

            $reg = trim((string) $xml_student_data->REGISTRATION_DATE);

            $stud_data->user_year = (string) $xml_student_data->YEAR_CLASS;
            $stud_data->user_college = (string) $xml_student_data->COLLEGE_CODE;
            $stud_data->user_major = (string) $xml_student_data->CURRIC_CODE;
            $stud_data->user_reg_status = $reg == 'null' ? NULL : $this->parse_date($reg);
            $stud_data->user_keypadid = (string) $xml_student_data->KEYPAD_ID;
            $stud_data->idnumber = trim((string)$xml_student_data->IDNUMBER);

            $student_data[$stud_data->idnumber] = $stud_data;
        }

        return $student_data;
    }
}

class xml_degree extends xml_source {

    function student_data($semester) {

        $response    = file_get_contents($this->xmldir.'DEGREE.xml');
        $xml_grads   = new SimpleXmlElement($this->clean_response($response));

        $graduates = array();
        foreach($xml_grads->ROW as $xml_grad) {
            $graduate = new stdClass;

            $graduate->idnumber = (string) $xml_grad->IDNUMBER;
            $graduate->user_degree = 'Y';

            $graduates[$graduate->idnumber] = $graduate;
        }

        return $graduates;
    }
}

class xml_anonymous extends xml_source {

    function student_data($semester) {

        $response    = file_get_contents($this->xmldir.'ANONYMOUS.xml');
        $xml_numbers = new SimpleXmlElement($this->clean_response($response));

        $numbers = array();
        foreach ($xml_numbers->ROW as $xml_number) {
            $number = new stdClass;

            $number->idnumber = (string) $xml_number->IDNUMBER;
            $number->user_anonymous_number = (string) $xml_number->LAW_ANONYMOUS_NBR;

            $numbers[$number->idnumber] = $number;
        }

        return $numbers;
    }
}

class xml_sports extends xml_source {

    /**
     * @todo refactor to take advantage of the DateTime classes
     * @param type $time
     * @return type
     */
    function find_season($time) {
        $now = getdate($time);

        $june = 610;
        $dec = 1231;

        $cur = (int)(sprintf("%d%02d", $now['mon'], $now['mday']));

        if ($cur >= $june and $cur <= $dec) {
            return ($now['year']) . substr($now['year'] + 1, 2);
        } else {
            return ($now['year'] - 1) . substr($now['year'], 2);
        }
    }

    function student_data($semester) {
        if ($semester->campus == 'LAW') {
            return array();
        }

        $now = time();

        // $xml_infos = $this->invoke(array($this->find_season($now)));
        $response    = file_get_contents($this->xmldir.'SPORTS.xml');
        $xml_infos   = new SimpleXmlElement($this->clean_response($response));

        $numbers = array();
        foreach ($xml_infos->ROW as $xml_info) {
            $number = new stdClass;

            $number->idnumber = (string) $xml_info->IDNUMBER;
            $number->user_sport1 = (string) $xml_info->SPORT_CODE_1;
            $number->user_sport2 = (string) $xml_info->SPORT_CODE_2;
            $number->user_sport3 = (string) $xml_info->SPORT_CODE_3;
            $number->user_sport4 = (string) $xml_info->SPORT_CODE_4;

            $numbers[$number->idnumber] = $number;
        }

        return $numbers;
    }
}
