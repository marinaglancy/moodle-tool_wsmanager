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

namespace tool_wsmanager\reportbuilder\local\filters;

use MoodleQuickForm;
use core_reportbuilder\local\helpers\database;

/**
 * Class service_filter
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_filter extends \core_reportbuilder\local\filters\base {
    /**
     * Setup form
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        global $DB;
        $options = $DB->get_records_menu(
            'external_services',
            null,
            'name ASC',
            'id, name'
        );

        $mform->addElement(
            'select',
            "{$this->name}_value",
            get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header()),
            ['' => 'Any'] + $options
        )->setHiddenLabel(true);
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql(); // SQL for the function name.
        $params = $this->filter->get_field_params();

        $serviceid = $values["{$this->name}_value"] ?? null;
        if (empty($serviceid)) {
            return ['', []];
        }

        $esfalias = database::generate_alias();
        $esalias = database::generate_alias();
        return [
            "EXISTS (SELECT 1 FROM {external_services_functions} $esfalias
                      JOIN {external_services} $esalias ON $esalias.id = $esfalias.externalserviceid
                     WHERE $esfalias.functionname = {$fieldsql} AND $esalias.id = :serviceid)",
            array_merge($params, ['serviceid' => $serviceid]),
        ];
    }

    /**
     * Return sample filter values
     *
     * @return array
     */
    public function get_sample_values(): array {
        return [
            "{$this->name}_values" => [1],
        ];
    }
}
