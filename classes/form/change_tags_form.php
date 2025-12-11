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

use core_form\dynamic_form;
use tool_wsmanager\helper;

/**
 * Class change_tags_form
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class change_tags_form extends dynamic_form {
    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/site:config', \context_system::instance());
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): \context {
        return \context_system::instance();
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/admin/tool/wsmanager/index.php');
    }

    #[\Override]
    public function process_dynamic_submission() {
        global $DB;
        $functions = $this->get_functions();
        $data = $this->get_data();
        $action = $data->action;
        $tags = $data->tags;
        $context = $this->get_context_for_dynamic_submission();

        // There are a lot of queries here but they are still fast and it's not a place where performance is critical.
        // It could be improved but I do not want to spend time on it.
        foreach ($tags as $tag) {
            foreach ($functions as $functionname => $itemid) {
                if ($action == 'add') {
                    \core_tag_tag::add_item_tag('tool_wsmanager', 'functions', $itemid, $context, $tag);
                } else if ($action == 'remove') {
                    \core_tag_tag::remove_item_tag('tool_wsmanager', 'functions', $itemid, $tag);
                }
            }
        }
    }

    /** @var array|null */
    protected $functions = null;

    /**
     * List of all functions that need to be changed
     *
     * @return array name=>id (id in tool_wsmanager_functions table)
     */
    protected function get_functions(): array {
        global $DB;
        if ($this->functions === null) {
            $functionnames = $this->optional_param('functionnames', '', PARAM_RAW_TRIMMED);
            [$sql, $params] = $DB->get_in_or_equal(preg_split('/\s*,\s*/', $functionnames, -1, PREG_SPLIT_NO_EMPTY));
            $this->functions = $DB->get_records_sql_menu(
                "SELECT ef.name, ws.id
                    FROM {external_functions} ef
                    JOIN {tool_wsmanager_functions} ws ON ws.name = ef.name
                    WHERE ef.name $sql",
                $params
            );
        }
        return $this->functions;
    }

    /**
     * Names of all functions that need to be changed
     *
     * @return array
     */
    protected function get_function_names(): array {
        return array_keys($this->get_functions());
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $functionnames = $this->get_function_names();
        $this->set_data([
            'functionnames' => join(',', $functionnames),
            'action' => $this->optional_param('action', '', PARAM_ALPHA),
        ]);
    }

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'functionnames');
        $mform->setType('functionnames', PARAM_RAW);
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement(
            'static',
            'functionnames_static',
            get_string('functionnames', 'tool_wsmanager'),
            join(', ', array_map('format_string', $this->get_function_names()))
        );

        $mform->addElement('tags', 'tags', get_string('tags'), [
            'component' => 'tool_wsmanager', 'itemtype' => 'functions',
        ]);
    }
}
