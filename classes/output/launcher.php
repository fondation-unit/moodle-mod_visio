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

class launcher implements renderable, templatable {

    public function __construct(
        $visioid,
        $userid,
        $firstname,
        $lastname,
        $isteacher = false,
        $roomurl,
        $apiurl,
        $broadcasturl,
        $accesstime,
        $passedtime
    ) {
        $this->visioid = $visioid;
        $this->userid = $userid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->isteacher = $isteacher;
        $this->roomurl = $roomurl;
        $this->apiurl = $apiurl;
        $this->broadcasturl = $broadcasturl;
        $this->accesstime = $accesstime;
        $this->passedtime = $passedtime;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $launcher
     * @return stdClass
     */
    public function export_for_template(renderer_base $launcher) {
        $data = new stdClass();
        $showbutton = false;
        $data->url = $this->roomurl;
        $data->ispassed = false;

        if (!$this->isteacher) {
            if (\time() >= $this->passedtime) {
                $data->url = isset($this->broadcasturl) ? $this->broadcasturl : '';
                $data->str = isset($this->broadcasturl) ? get_string('broadcastview', 'visio') : get_string('broadcastsoon', 'visio');
                $data->ispassed = true;
                $showbutton = isset($this->broadcasturl) ? true : false;
            } else if (\time() >= $this->accesstime) {
                $data->url = $this->roomurl . '/?guestName=' . $this->firstname . ' ' . $this->lastname;
                $data->str = get_string('modulename', 'visio');
                $showbutton = true;
            } else {
                $data->str = get_string('early_access', 'visio');
                $showbutton = false;
            }
        } else {
            if (\time() >= $this->passedtime) {
                $data->url = isset($this->broadcasturl) ? $this->broadcasturl : '';
                $data->str = isset($this->broadcasturl) ? get_string('broadcastview', 'visio') : get_string('broadcastsoon', 'visio');
                $data->ispassed = true;
            } else if (\time() >= $this->accesstime) {
                $data->url = $this->roomurl;
            } else {
                $data->url = $this->roomurl;
                $data->str = get_string('early_access', 'visio');
            }
        }

        return [
            'isTeacher' => $this->isteacher,
            'showButton' => $showbutton,
            'data' => $data,
            'currentuser' => $this->userid,
            'visioid' => $this->visioid,
            'apiurl' => $this->apiurl
        ];
    }
}
