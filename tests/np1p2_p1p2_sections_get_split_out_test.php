<?php
global $CFG;
require_once 'local_xml_testcase_base.php';
require_once $CFG->dirroot.'/group/lib.php';
require_once $CFG->dirroot.'/group/lib.php';

/**
 * This test class tests the following enrollment scenario:
 * 
 * Given any course with multiple sections, there is a primary instructor assigned to 
 * each of those sections. There is also another instructor assigned as non-primary
 * for one of those sections. At some point, the non-primary is promoted to be the 
 * primary instructor of the section.
 * 
 * This test ensures that the following desired behavior occurs:
 * The section for the promoted instructor should be removed from the original 
 * course and added to a new course for the promoted instructor (with all enrollments). 
 * 
 * The promoted instructor should retain his role in the original course and group, 
 * but the group should not have any students enrolled. This is an administrative
 * decision that allows such instructors to retain access to any course materials they 
 * may have created in the original course. While it may be a nuisance for the original
 * course primary instructor to still have the promoted teacher in the course, it
 * is preferable to divorcing the promoted teacher from his/her potential IP.
 */
class np1p2_p1p2_sections_get_split_out_testcase extends local_xml_testcase_base {

    static $local_datadir = 'np1p2_p1p2_sections_get_split_out/';
    
    public function test_step1_inst4Course(){
        global $DB;
        
        //run cron against initial dataset - step 1
        $this->run_cron_until_step(1);
        
        //get users
        $inst4 = $DB->get_record('user', array('username'=>'inst4'));
        $inst3 = $DB->get_record('user', array('username'=>'inst3'));
        
        // ensure course for inst4 exists
        $course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor four');
        $this->assertTrue((bool)$course);

        // inst4 is primary; inst3 has non-primary assignment to sec 003, 6 + 5 students
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'teacher', $course->fullname));
        $this->assertTrue($this->userHasRoleInCourse('inst4', 'editingteacher', $course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $course->fullname)));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('teacher', $course->fullname)));
        $this->assertEquals(11,  count($this->usersWithRoleInCourse('student', $course->fullname)));

        // should have 2 sections
        $groups = $this->getGroupsForCourse($course->id);
        $this->assertEquals(2, count($groups));

        // now be sure they are the correct groups
        $correctSections = array('TST2 2011 002', 'TST2 2011 003');
        foreach($groups as $g){
            $this->assertTrue(in_array($g->name, $correctSections));
        }

        //get section 002
        $sec2 = $this->getGroupByNameInCourse('TST2 2011 002', $course->id);

        // 002 has 6 students + 1 inst4 as primary
        $this->assertEquals(1, $this->count_groups_members_by_role($this->editingteacherRole, $sec2));
        $this->assertEquals(6, $this->count_groups_members_by_role($this->studentRole, $sec2));

        //check that the right person has the role
        $this->assertTrue($this->user_has_group_role('editingteacher', $sec2->id, $course->id, $inst4->id));

        //get section 003
        $sec3 = $this->getGroupByNameInCourse('TST2 2011 003', $course->id);

        // 003 has 5 students + inst4 is primary, inst3 is non-primary
        $this->assertEquals(1, $this->count_groups_members_by_role($this->editingteacherRole, $sec3));
        $this->assertEquals(1, $this->count_groups_members_by_role($this->teacherRole, $sec3));
        $this->assertEquals(5, $this->count_groups_members_by_role($this->studentRole, $sec3));

        // //check that the right person has the role - inst3(np) and inst4(p) assigned
        $this->assertTrue($this->user_has_group_role('teacher', $sec3->id, $course->id, $inst3->id));
        $this->assertTrue($this->user_has_group_role('editingteacher', $sec3->id, $course->id, $inst4->id));

        $this->assertEquals(1, $course->visible);

        $this->endOfStep();
    }

    public function test_step1_inst3Course(){
        global $DB;

        // run cron against initial dataset - step 1
        $this->run_cron_until_step(1);
        
        // get user 
        $inst3 = $DB->get_record('user', array('username'=>'inst3'));

        // ensure course for inst3 exists
        $course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor three');
        $this->assertTrue((bool)$course);

        // inst3 has primary assignment to sec 004, 2 students
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', $course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $course->fullname)));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('teacher', $course->fullname)));
        $this->assertEquals(2,  count($this->usersWithRoleInCourse('student', $course->fullname)));

        // should have 1 section only
        $groups = $this->getGroupsForCourse($course->id);
        $this->assertEquals(1, count($groups));

        // 004 has 2 students + 1 instructor
        $sec4 = $this->getGroupByNameInCourse('TST2 2011 004', $course->id);
        $this->assertEquals(1, $this->count_groups_members_by_role($this->editingteacherRole, $sec4));
        $this->assertEquals(2, $this->count_groups_members_by_role($this->studentRole, $sec4));

        $this->assertTrue($this->user_has_group_role('editingteacher', $sec4->id, $course->id, $inst3->id));

        $this->assertEquals(1, $course->visible);

        $this->endOfStep();
    }
    
    public function test_step2_inst4Course(){
        global $CFG, $DB;

        // testing state at step 2
        $this->run_cron_until_step(2);

        // get isntructor users
        $inst3 = $DB->get_record('user', array('username'=>'inst3'));
        $inst4 = $DB->get_record('user', array('username'=>'inst4'));

        // ensure course, sec 002, for inst4 exists
        $course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor four');
        $this->assertTrue((bool)$course);

        // get section objects
        $sec2 = $this->getGroupByNameInCourse('TST2 2011 002', $course->id);
        $sec3 = $this->getGroupByNameInCourse('TST2 2011 003', $course->id);

        // inst4 is the only primary instructor
        $this->assertTrue($this->userHasRoleInCourse('inst4', 'editingteacher', $course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $course->fullname)));

        // inst3 remains in the course, assigned to sec 003 as non-primary instructor
        // this is an administrative decision to prevent such instructors from losing access
        // to materials that they may have created.
        $this->assertTrue($this->userHasRoleInCourse('inst4', 'editingteacher', $course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $course->fullname)));

        // both sections 002 and 003 should still exist
        $groups = $this->getGroupsForCourse($course->id);
        $this->assertEquals(2, count($groups));

        // should have 6 total students
        $this->assertEquals(6,  count($this->usersWithRoleInCourse('student', $course->fullname)));

        // should only have section 002 students
        $this->assertEquals(6, $this->count_groups_members_by_role($this->studentRole, $sec2));
        $this->assertEquals(0, $this->count_groups_members_by_role($this->studentRole, $sec3));

        //course should be visible
        $this->assertEquals(1, $course->visible);

        $this->endOfStep();
    }
    
    public function test_step2_inst3Course(){
        global $DB;

        // testing state at step 2
        $this->run_cron_until_step(2);

        // get isntructor users
        $inst3 = $DB->get_record('user', array('username'=>'inst3'));
        $inst4 = $DB->get_record('user', array('username'=>'inst4'));

        // ensure course, sec 002, for inst4 exists
        $course = $this->getCourseIfExists('2014 Spring TST2 2011 for instructor three');
        $this->assertTrue((bool)$course);

        // get section objects
        $sec3 = $this->getGroupByNameInCourse('TST2 2011 003', $course->id);
        $sec4 = $this->getGroupByNameInCourse('TST2 2011 004', $course->id);

        // inst3 is the only primary instructor
        $this->assertTrue($this->userHasRoleInCourse('inst3', 'editingteacher', $course->fullname));
        $this->assertEquals(1,  count($this->usersWithRoleInCourse('editingteacher', $course->fullname)));
        $this->assertEquals(0,  count($this->usersWithRoleInCourse('teacher', $course->fullname)));

        // section 003 and 004 should now exist in the course
        $groups = $this->getGroupsForCourse($course->id);
        $this->assertEquals(2, count($groups));
        
        // should have 7 total students
        $this->assertEquals(7,  count($this->usersWithRoleInCourse('student', $course->fullname)));

        // 5 students in section 003
        $this->assertEquals(5, $this->count_groups_members_by_role($this->studentRole, $sec3));
        
        // 2 students in section 004
        $this->assertEquals(2, $this->count_groups_members_by_role($this->studentRole, $sec4));

        //course should be visible
        $this->assertEquals(1, $course->visible);

        $this->endOfStep();
    }
}