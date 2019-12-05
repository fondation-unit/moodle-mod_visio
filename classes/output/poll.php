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

class poll implements renderable, templatable {

    public function __construct(
        $visioid,
        $userid,
        $firstname,
        $lastname,
        $isteacher = false
    ) {
        $this->visioid = $visioid;
        $this->userid = $userid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->isteacher = $isteacher;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $launcher
     * @return stdClass
     */
    public function export_for_template(renderer_base $launcher) {
        global $DB;

        $data = array();
        $visio = $DB->get_record('visio', array('id' => $this->visioid), '*', MUST_EXIST);
        $poll = $DB->get_record('visio_polls', array(
            'visioid' => $this->visioid,
            'userid' => $this->userid
        ), '*');

        if ($this->isteacher) {
            $total = $DB->count_records('visio_polls', array('visioid' => $this->visioid));

            for ($i = 1; $i < 6; $i++) {
                $count = $DB->count_records('visio_polls', array(
                    'visioid' => $this->visioid,
                    'poll_value' => $i
                ));

                $name = "polltime_$i";
                if ($visio->{$name} > 0) {
                    $obj = new stdClass();
                    $obj->pollname = $i;
                    $obj->time = date('d/m/Y', $visio->{$name}). ' ' .get_string("to"). ' ' .date('H:i', $visio->{$name});
                    $obj->selected = false;
                    $obj->count = round(($count / $total) * 100);
                    
                    $data[] = $obj;
                }
            }
        } else {
            for ($i = 1; $i < 6; $i++) {
                $name = "polltime_$i";
                if ($visio->{$name} > 0) {
                    $obj = new stdClass();
                    $obj->pollname = $i;
                    $obj->time = date('d/m/Y', $visio->{$name}). ' ' .get_string("to"). ' ' .date('H:i', $visio->{$name});
                    $obj->selected = $poll && $poll->poll_value == $i ? true : false;
                    
                    $data[] = $obj;
                }
            }
        }
        
        return [
            'visioid' => $this->visioid,
            'polldates' => $data,
            'isteacher' => $this->isteacher
        ];
    }
}
