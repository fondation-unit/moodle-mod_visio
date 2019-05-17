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
 * @package    mod_visio
 * @copyright  2019 Pierre Duverneix - Fondation UNIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'mod_visio_host_launch_visio' => array(
        'classname'     => 'mod_visio_external',
        'methodname'    => 'host_launch_visio',
        'description'   => 'Host launches a visio',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/course:manageactivities'
    ),
    'mod_visio_set_presence' => array(
        'classname'     => 'mod_visio_external',
        'methodname'    => 'set_presence',
        'description'   => 'Set the presence of a user',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/course:manageactivities'
    )
);
