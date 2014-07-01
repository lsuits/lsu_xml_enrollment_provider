<?php
/// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_xml
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



abstract class xml_source {

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
     * Get the contents of the password file.
     * @return \DOMDocument|boolean
     * @throws Exception
     */
    public function getinitialpasswds(){

        $filename = $this->xmldir."INIT_PASSWD.xml";
        if(!file_exists($filename)){
            return false;
        }else{
            $xml = new DOMDocument();
            $xml->load($filename);
            $xsd = dirname(__FILE__)."/schema/INIT_PASSWD.xsd";

            if(!file_exists($xsd)){
                throw new exception("Failed to locate schema file {$xsd}");
            }

            if(!$xml->schemaValidate($xsd)){
                throw new Exception("Could not validate XML for password file using schema {$xsd}");
            }else{
                return $xml;
            }
        }
    }

    public function lookupuserpasswd(stdClass $user, DOMXpath $xpath){

       $query   = sprintf("//ROW[IDNUMBER/text() = '%s']/INIT_PASSWD", $user->idnumber);
       $passwds = $xpath->query($query);

       if(!$passwds || $passwds->length == 0){
           return false;
       }
       $passwd  = $passwds->item(0)->nodeValue;
	   return $passwd;
    }
}

abstract class xml_teacher_format extends xml_source {
    public function format_teacher($xml_teacher) {
        $primary_flag = trim($xml_teacher->PRIMARY_INSTRUCTOR);

        list($first, $last) = $this->parse_name($xml_teacher->INDIV_NAME);

        $teacher = new stdClass;

        $teacher->idnumber     = (string) $xml_teacher->IDNUMBER;
        $teacher->primary_flag = (string) $primary_flag == 'Y' ? 1 : 0;
        $teacher->firstname    = $first;
        $teacher->lastname     = $last;
        $teacher->username     = (string) $xml_teacher->PRIMARY_ACCESS_ID;

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

        $student->username  = (string) $xml_student->PRIMARY_ACCESS_ID;
        $student->firstname = $first;
        $student->lastname  = $last;
        $student->user_ferpa = trim((string)$xml_student->WITHHOLD_DIR_FLG) == 'P' ? 1 : 0;

        return $student;
    }
}
