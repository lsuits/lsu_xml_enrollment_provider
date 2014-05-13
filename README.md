# UES XML Enrollment Provider

This Moodle plugin enhances the UES enrollment with XML-based enrollment
information.

For more information about UES and the enrollment process, please go to the
[UES wiki][ues].

[ues]: https://github.com/lsuits/ues/wiki

## Features

- `pre` and `post` process support for user meta information
- Custom error handlers for provider specific information
- Admin links for on-demand user data processing

## Installation

XML enrollment provider installs as a Moodle [local plugin][local] to be
used with UES. Once installed, UES must be configured to use it.

[local]: http://docs.moodle.org/dev/Local_plugins

##Configuration

XML files containing enrollment data are expected to be located in a directory within your Moodle data root.

##Schema
forthcoming...

## License

XML enrollment adopts the same license that Moodle itself does.

##Known Issues
1. In the scenario where a non-primary, np1, of a course, c1 is promoted 
to primary instructor, p1, of c1, AND THEN the course is re-assigned 
to some other primary instructor p2, ALL enrollments are dropped from 
the course c1, including both roles for the instructor (np1, p1). __won't fix__
