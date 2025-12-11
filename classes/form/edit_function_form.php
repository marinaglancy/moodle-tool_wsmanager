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
 * Class edit_function_form
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_function_form extends dynamic_form {
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

    /** @var \stdClass|null cached function data */
    protected $functiondata = null;

    /**
     * Retrieve function details (both from external_functions and tool_wsmanager_functions tables)
     *
     * @return \stdClass|null
     */
    protected function get_function_data(): \stdClass {
        global $DB;
        if ($this->functiondata == null) {
            $functionname = $this->optional_param('functionname', '', PARAM_RAW);
            $record = $DB->get_record_sql("SELECT ef.*,
                    twf.id as ws_id, twf.description, twf.updated_description, twf.parameters_desc, twf.returns_desc,
                    twf.allowed_from_ajax, twf.type, twf.loginrequired
                FROM {external_functions} ef
                LEFT JOIN {tool_wsmanager_functions} twf ON ef.name = twf.name
                WHERE ef.name = ?", [$functionname], MUST_EXIST);
            if (!$record->ws_id) {
                // Race condition? The record does not exist in tool_wsmanager_functions. Create it.
                $data = (new helper())->prepare_function_info($record);
                $record->ws_id = $DB->insert_record('tool_wsmanager_functions', ['name' => $record->name] + $data);
                $props = ['description', 'parameters_desc', 'returns_desc', 'allowed_from_ajax', 'type', 'loginrequired'];
                foreach ($props as $field) {
                    $record->$field = $data[$field];
                }
            }
            $this->functiondata = $record;
        }
        return $this->functiondata;
    }

    #[\Override]
    public function process_dynamic_submission() {
        global $DB;
        $record = $this->get_function_data();

        $description = $this->get_data()->updated_description ?? '';
        if ($description === $record->description || trim($description ?? '') === '') {
            $description = null;
        }

        if ($description !== $record->updated_description) {
            // Update all_texts field.
            $record->updated_description = $description;
            $alltexts = helper::prepare_all_text((array)$record);
            $DB->update_record('tool_wsmanager_functions', [
                'id' => $record->ws_id,
                'updated_description' => $description,
                'all_texts' => $alltexts,
            ]);
        }
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $record = $this->get_function_data();
        $this->set_data([
            'description' => $record->description,
            'updated_description' => $record->updated_description,
            'functionname' => $record->name,
        ]);
    }

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'functionname');
        $mform->setType('functionname', PARAM_RAW);
        $data = $this->get_function_data();

        $mform->addElement(
            'static',
            'name',
            get_string('functionname', 'tool_wsmanager'),
            '<b>' . format_string($data->name) . '</b>'
        );

        $mform->addElement(
            'textarea',
            'updated_description',
            get_string('description', 'tool_wsmanager'),
            null,
            ['maxfiles' => 0, 'noclean' => true, 'trusttext' => false]
        );

        $d = $mform->addElement(
            'textarea',
            'description',
            get_string('originaldescription', 'tool_wsmanager'),
        );
        $mform->hardFreeze('description');

        $mform->addElement(
            'static',
            'functionheader',
            '',
            helper::function_badges($data)
        );
    }
}
