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

namespace tool_wsmanager\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/datalib.php');

/**
 * Class bulk_action_form
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_action_form extends moodleform {
    /** @var array|null cached value of all services */
    protected $allservices = null;

    /**
     * Get all external services
     *
     * @return array
     */
    protected function get_all_services(): array {
        global $DB;
        if ($this->allservices === null) {
            $this->allservices = $DB->get_records_menu(
                'external_services',
                null,
                'name ASC',
                'id, name'
            );
        }
        return $this->allservices;
    }

    /**
     * Returns an array of actions
     *
     * @return array of action_link objects
     */
    public function get_actions(): array {
        $actions = ['' => [0 => get_string('choose') . '...']];
        $addtoservice = get_string('addtoservice', 'tool_wsmanager');
        $removefromservice = get_string('removefromservice', 'tool_wsmanager');
        $actions[$addtoservice] = [];
        $actions[$removefromservice] = [];
        foreach ($this->get_all_services() as $id => $name) {
            $actions[$addtoservice]['add-service-' . $id] = format_string($name);
            $actions[$removefromservice]['remove-service-' . $id] = format_string($name);
        }
        $tagscategory = get_string('tags', 'moodle');
        $actions[$tagscategory]['add-tag'] = get_string('addtag', 'tool_wsmanager');
        $actions[$tagscategory]['remove-tag'] = get_string('removetag', 'tool_wsmanager');

        return $actions;
    }

    /**
     * Form definition
     */
    public function definition() {
        $mform =& $this->_form;

        // All bulk actions are processed in ajax, so we shouldn't trigger formchange warnings.
        $mform->disable_form_change_checker();

        $mform->addElement('hidden', 'userids');
        $mform->setType('userids', PARAM_SEQUENCE);

        $mform->addElement('selectgroups', 'action', get_string('withselectedfunctions', 'tool_wsmanager'), $this->get_actions());
    }
}
