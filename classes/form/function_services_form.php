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

/**
 * Class function_services_form
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class function_services_form extends dynamic_form {
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
        $existing = $DB->get_fieldset_sql("SELECT
            es.id
            FROM {external_services_functions} esf
            JOIN {external_services} es ON esf.externalserviceid = es.id
            WHERE esf.functionname = ?", [$this->get_data()->functionname]);
        $newserviceids = [];
        $data = $this->get_data();
        foreach ($data as $key => $value) {
            if (strpos($key, 'service_') === 0 && $value) {
                $newserviceids[] = (int)substr($key, strlen('service_'));
            }
        }

        $toadd = array_diff($newserviceids, $existing);
        $toremove = array_diff($existing, $newserviceids);

        foreach ($toadd as $serviceid) {
            $record = ['externalserviceid' => $serviceid, 'functionname' => $data->functionname];
            $DB->insert_record('external_services_functions', $record);
        }

        foreach ($toremove as $serviceid) {
            $DB->delete_records(
                'external_services_functions',
                ['externalserviceid' => $serviceid, 'functionname' => $data->functionname]
            );
        }
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $functionname = $this->optional_param('functionname', '', PARAM_RAW);
        $serviceids = $DB->get_fieldset_sql("SELECT
            es.id
            FROM {external_services_functions} esf
            JOIN {external_services} es ON esf.externalserviceid = es.id
            WHERE esf.functionname = ?", [$functionname]);
        $data = ['functionname' => $functionname];
        foreach ($serviceids as $serviceid) {
            $data["service_$serviceid"] = 1;
        }
        $this->set_data($data);
    }

    #[\Override]
    protected function definition() {
        $services = $this->get_all_services();
        $mform = $this->_form;
        $mform->addElement('hidden', 'functionname');
        $mform->setType('functionname', PARAM_RAW);

        $mform->addElement(
            'static',
            'functionheader',
            '',
            get_string(
                'selectservicesforfunction',
                'tool_wsmanager',
                $this->optional_param('functionname', '', PARAM_ALPHANUMEXT)
            )
        );

        foreach ($services as $serviceid => $servicename) {
            $mform->addElement('advcheckbox', "service_$serviceid", '', $servicename);
            $mform->setType("service_$serviceid", PARAM_INT);
        }
    }
}
