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

namespace tool_wsmanager;

/**
 * Class helper
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Re-builds the cache table tool_wsmanager_functions
     *
     * @return void
     */
    public function update_cache_table() {
        global $DB;

        $records = $DB->get_records('external_functions');
        $cacherecords = $DB->get_records('tool_wsmanager_functions');
        $cache = [];
        foreach ($cacherecords as $record) {
            $cache[$record->name] = $record;
        }

        foreach ($records as $record) {
            $cacheid = $cache[$record->name]->id ?? null;
            if ($cacheid) {
                $record->updated_description = $cache[$record->name]->updated_description;
            }
            $data = $this->prepare_function_info($record);
            if ($cacheid) {
                $updateneeded = false;
                foreach ($data as $field => $value) {
                    if ($value !== $cache[$record->name]->$field) {
                        $updateneeded = true;
                        break;
                    }
                }
                if ($updateneeded) {
                    $DB->update_record('tool_wsmanager_functions', ['id' => $cacheid] + $data);
                }
            } else {
                $cacheid = $DB->insert_record('tool_wsmanager_functions', $data);
            }
        }
    }

    /**
     * Prepare function information for inserting into cache table tool_wsmanager_functions
     *
     * @param \stdClass $record
     * @return array
     */
    public function prepare_function_info(\stdClass $record): array {
        $function = \core_external\external_api::external_function_info($record);
        $data = [
            'name' => $function->name,
            'description' => $function->description,
            'allowed_from_ajax' => empty($function->allowed_from_ajax) ? 0 : 1,
            'type' => $function->type,
            'parameters_desc' => json_encode($this->prepare_value($function->parameters_desc)),
            'returns_desc' => json_encode($this->prepare_value($function->returns_desc)),
            'loginrequired' => empty($function->loginrequired) ? 0 : 1,
            'readonlysession' => empty($function->readonlysession) ? 0 : 1,
        ];
        $data['all_texts'] = self::prepare_all_text($data);
        return $data;
    }

    /**
     * Prepare value of 'all_texts' field (concatenation of all text fields, used for searching)
     *
     * @param array $data
     * @return string
     */
    public static function prepare_all_text(array $data) {
        return implode(' ', [
            $data['name'],
            $data['description'],
            $data['updated_description'] ?? '',
            $data['parameters_desc'],
            $data['returns_desc'],
        ]);
    }

    /**
     * Prepares the value to be json-encoded (recursive method)
     *
     * @param mixed $value
     * @return array|string|float|bool
     */
    protected function prepare_value($value) {
        if ($value instanceof \core_external\external_description || is_array($value) || is_object($value)) {
            $rv = [];
            if ($value instanceof \core_external\external_description) {
                $rv['class'] = join(',', self::class_name(get_class($value)));
                if ($value->required != VALUE_DEFAULT) {
                    // Remove occasional mess in WS definitions that is not picked up by core because core ignores
                    // the property default unless the value should have a default.
                    $value->default = null;
                }
            }
            foreach ($value as $k => $v) {
                $rv[$k] = self::prepare_value($v);
            }
        } else {
            $rv = $value;
        }
        return $rv;
    }

    /**
     * Returns a list of class names in the inheritance chain
     *
     * Only returns the last part of the namespaced classes.
     * Stops at external_description (abstract class that is always the parent).
     *
     * @param string|false $classname
     * @return array
     */
    protected function class_name($classname): array {
        $shortclassname = $classname ? preg_replace('/^(.*\\\\)/', '', $classname) : '';
        if ($shortclassname == "external_description" || empty($classname)) {
            return [];
        }
        return array_merge([$shortclassname], self::class_name(get_parent_class($classname)));
    }

    /**
     * Type/ajax/guest badges for displaying the function details
     *
     * @param \stdClass $record record containing fields from tool_wsmanager_functions table
     * @return string
     */
    public static function function_badges(\stdClass $record): string {
        $badges = '';
        if (!empty($record->type)) {
            $typeclass = $record->type === 'write' ? 'badge-success' : 'badge-secondary';
            $badges .= \html_writer::tag('span', format_string($record->type), ['class' => 'badge ' . $typeclass . ' me-1']);
        }
        if (!empty($record->allowed_from_ajax)) {
            $badges .= \html_writer::tag('span', 'AJAX', ['class' => 'badge badge-info me-1']);
        }
        if (isset($record->loginrequired) && !$record->loginrequired) {
            $badges .= \html_writer::tag('span', 'Guests', ['class' => 'badge badge-warning me-1']);
        }
        return $badges;
    }

    /**
     * Adds or removes external functions to/from a service
     *
     * @param array $functionnames
     * @param int $serviceid
     * @param string $action
     * @return void
     */
    public function add_function_to_service(array $functionnames, int $serviceid, string $action = 'add') {
        global $DB;
        if ($action != 'add' && $action != 'remove') {
            throw new \moodle_exception("Action parameter must be either 'add' or 'remove'");
        }

        // Validate service exists.
        $service = $DB->get_record('external_services', ['id' => $serviceid], '*', MUST_EXIST);

        // Find which functions are already allocated to this service.
        [$sql, $params] = $DB->get_in_or_equal($functionnames, SQL_PARAMS_NAMED);
        $records = $DB->get_records_sql("SELECT ef.*, esf.id AS esfid
            FROM {external_functions} ef
            LEFT JOIN {external_services_functions} esf ON
                ef.name = esf.functionname AND esf.externalserviceid = :serviceid
            WHERE ef.name $sql", $params + ['serviceid' => $service->id]);
        if (empty($records)) {
            // No functions found.
            return;
        }

        foreach ($records as $record) {
            if ($action == 'add' && empty($record->esfid)) {
                $DB->insert_record('external_services_functions', [
                    'functionname' => $record->name,
                    'externalserviceid' => $service->id,
                ]);
            }
            if ($action == 'remove' && !empty($record->esfid)) {
                $DB->delete_records('external_services_functions', ['id' => $record->esfid]);
            }
        }
    }
}
