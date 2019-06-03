<?php
// This file is part of Moodle - http://moodle.org/
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
 *
 * @copyright  2019 Pierre Duverneix <pierre.duverneix@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_visio\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class visio_table implements renderable, templatable {

    public function __construct($visioid, $userid, $courseid) {
        $this->visioid = $visioid;
        $this->userid = $userid;
        $this->courseid = $courseid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = array();

        $usersgroups = groups_get_user_groups($this->courseid, $this->userid);

        foreach ($usersgroups as $groups) {
            foreach ($groups as $group) {
                $members = groups_get_members($group, 'u.id,u.firstname,u.lastname,u.email', 'u.id ASC');
                foreach ($members as $member) {
                    if ($USER->id != $member->id) { // Avoid adding the teachers in the table.
                        $row = new stdClass();
                        $row->id = $member->id;
                        $row->firstname = $member->firstname;
                        $row->lastname = $member->lastname;
                        $row->groups = groups_get_group_name($group);
                        $row->email = $member->email;

                        $data[] = $row;
                    }
                }
            }
        }

        return [
            'hasUsers' => count($data) ? true : false,
            'users' => $data,
            'visioid' => $this->visioid,
            'presentstr' => get_string('present_str', 'visio'),
            'missingstr' => get_string('missing_str', 'visio')
        ];
    }
}
