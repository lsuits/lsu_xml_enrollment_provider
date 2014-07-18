##Sample enrollment files

The files in this directory match the various data sources that UES can handle.

At a minimum, UES enrollment requires 

* `SEMESTERS.xml`
* `COURSES.xml`
* `STUDENTS.xml`
* `INSTRUCTORS.xml`
* `INIT_PASSWD.xml` (if using manual authentication)

```
<?xml version="1.0" encoding="UTF-8" ?>
<rows xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:noNamespaceSchemaLocation="../schema/SEMESTERS.xsd">
  <ROW>
    <YEAR>2014</YEAR>
    <NAME>Some Old Semester</NAME>
    <CAMPUS>Main</CAMPUS>
    <SESSION_KEY></SESSION_KEY>
    <CLASSES_START>1401580800</CLASSES_START>
    <GRADES_DUE>1409529600</GRADES_DUE>
  </ROW>
</rows>
```
1. If you're not using seperate semesters, please choose sane defualts for the following:
  1. "YEAR"
  1. "NAME"
  1. "CAMPUS"
1. "SESSION_KEY" can be left blank.
1. "CLASSES_START" should be defined in seconds from epoch.
1. "GRADES_DUE" is the same as classes end and should be defined in seconds from epoch.

```
<?xml version="1.0" encoding="UTF-8" ?>
<rows xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="../schema/COURSES.xsd">
    <ROW>
        <CAMPUS_CODE>01</CAMPUS_CODE>
        <DEPT_CODE>Biology</DEPT_CODE>
        <COURSE_NBR>101c</COURSE_NBR>
        <SECTION_NBR>001</SECTION_NBR>
        <CLASS_TYPE>LEC</CLASS_TYPE>
        <COURSE_TITLE>Intro to Organic Darwinism</COURSE_TITLE>
        <GRADE_SYSTEM_CODE>L</GRADE_SYSTEM_CODE>
    </ROW>
</rows>
```
1. "CAMPUS_CODE" should be left as "01" for now.
1. "DEPT_CODE" will define the course category in which the course will exist within Moodle. It will also be used to generate the course name and for enrollment.
1. "COURSE_NBR" is used in course name generation and for enrollment.
1. "SECTION_NBR" is used when wanting to assign multiple groups to a course within Moodle. If you do not want to use separate groups, please use "001" throughout.
1. "CLASS_TYPE" should always be LEC.
1. "COURSE_TITLE" is the long name for the course.
1. "GRADE_SYSTEM_CODE" should be left as wither L (letters), N (numeric) or LP (pass/fail). This is merely for organizational reasons and is not used within the enrollent system. It's best to leave this as N.

```
<?xml version="1.0" encoding="UTF-8" ?>
<rows xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="../schema/INSTRUCTORS.xsd">
    <ROW>
        <IDNUMBER>234555</IDNUMBER>
        <PRIMARY_INSTRUCTOR>Y</PRIMARY_INSTRUCTOR>
        <CLASS_DEPT_CODE>Biology</CLASS_DEPT_CODE>
        <CLASS_COURSE_NBR>101c</CLASS_COURSE_NBR>
        <SECTION_NBR>001</SECTION_NBR>
        <INDIV_NAME>two, instructor</INDIV_NAME>
        <PRIMARY_ACCESS_ID>inst2</PRIMARY_ACCESS_ID>
    </ROW>
</rows>
```
1. "IDNUMBER" is the Moodle idnumber of the instructor.
1. "PRIMARY_INSTRUCTOR" should always be Y unless you have specific lesser instructors within a course.
1. "CLASS_DEPT_CODE" should match "DEPT_CODE" in COURSES.xml and controlls the instructor's enrollment within the course.
1. "CLASS_COURSE_NBR" should match "COURSE_NBR" in COURSES.xml and controlls the instructor's enrollment within the course.
1. "SECTION_NBR" defines what group students and instructors are placed in within Moodle. If you're not using groups, leave this as 001 for everyone.
1. "INDIV_NAME" is the last, first name for the instructor in question.
1. "PRIMARY_ACCESS_ID" is the username for the instructor. This is the name they log into Moodle with. It can be the same as "IDNUMBER".

```
<?xml version="1.0" encoding="UTF-8" ?>
<rows xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:noNamespaceSchemaLocation="../schema/STUDENTS.xsd">
    <ROW>
        <IDNUMBER>12600</IDNUMBER>
        <DEPT_CODE>Biology</DEPT_CODE>
        <COURSE_NBR>101c</COURSE_NBR>
        <SECTION_NBR>001</SECTION_NBR>
        <CREDIT_HRS>1.00</CREDIT_HRS>
        <INDIV_NAME>student, Jane</INDIV_NAME>
        <PRIMARY_ACCESS_ID>jstudent1</PRIMARY_ACCESS_ID>
        <WITHHOLD_DIR_FLG>L</WITHHOLD_DIR_FLG>
    </ROW>
    <ROW>
        <IDNUMBER>12601</IDNUMBER>
        <DEPT_CODE>Biology</DEPT_CODE>
        <COURSE_NBR>101c</COURSE_NBR>
        <SECTION_NBR>001</SECTION_NBR>
        <CREDIT_HRS>1.00</CREDIT_HRS>
        <INDIV_NAME>student, John</INDIV_NAME>
        <PRIMARY_ACCESS_ID>jstudent2</PRIMARY_ACCESS_ID>
        <WITHHOLD_DIR_FLG>L</WITHHOLD_DIR_FLG>
    </ROW>
</rows>
```
1. "IDNUMBER" is the Moodle idnumber for the student. 
1. "DEPT_CODE" should match "DEPT_CODE" in COURSES.xml and "CLASS_DEPT_CODE" in INSTRUCTORS.xml and controlls the student's enrollment within the course.
1. "COURSE_NBR" should match "COURSE_NBR" in COURSES.xml and "CLASS_COURSE_NBR" in INSTRUCTORS.xml and controlls the student's enrollment within the course.
1. "SECTION_NBR" should match "SECTION_NBR" in INSTRUCTORS.xml as defined in the matching row.
1. "CREDIT_HRS" can be anything. Use 1.00 if you're not tracking this.
1. "INDIV_NAME" The name of the student in lastname, firstname format.
1. "PRIMARY_ACCESS_ID" is the username for the student. This is the name they log into Moodle with. It can be the same as "IDNUMBER". 
1. "WITHHOLD_DIR_FLG" should be left as "L" unless you want to enforce FERPA compliance.
