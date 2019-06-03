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

    public function __construct($visioid, $userid, $externalurl, $accesstime, $passedtime) {
        $this->visioid = $visioid;
        $this->userid = $userid;
        $this->externalurl = $externalurl;
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

        if (time() >= $this->passedtime) {
            if (isset($this->externalurl)) {
                $showbutton = true;
                $data->url = $this->externalurl;
                $data->str = get_string('broadcastview', 'visio');
            } else {
                $showbutton = false;
                $data->url = '';
                $data->str = get_string('late_access', 'visio');
            }
        } else if ($this->accesstime <= time()) {
            $showbutton = true;
            $data->url = $this->externalurl;
            $data->str = get_string('modulename', 'visio');
        } else {
            $showbutton = false;
            $data->url = '';
            $data->str = get_string('early_access', 'visio');
        }

        return [
            'showButton' => $showbutton,
            'data' => $data,
            'currentuser' => $this->userid,
            'visioid' => $this->visioid
        ];
    }
}
