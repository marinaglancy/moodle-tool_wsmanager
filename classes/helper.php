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
        $cache = $DB->get_records(
            'tool_wsmanager_functions',
            [],
            '',
            'function_id, id, updated_description'
        );
        foreach ($records as $record) {
            $function = \core_external\external_api::external_function_info($record);
            $data = [
                'description' => $function->description,
                'allowed_from_ajax' => empty($function->allowed_from_ajax) ? 0 : 1,
                'type' => $function->type,
                'parameters_desc' => json_encode($this->prepare_value($function->parameters_desc)),
                'returns_desc' => json_encode($this->prepare_value($function->returns_desc)),
                'loginrequired' => empty($function->loginrequired) ? 0 : 1,
                'readonlysession' => empty($function->readonlysession) ? 0 : 1,
            ];
            $data['all_texts'] = implode(' ', [
                $data['description'],
                $data['parameters_desc'],
                $data['returns_desc'],
            ]);
            $cacheid = $cache[$record->id]->id ?? null;
            if ($cacheid) {
                $data['all_texts'] .= ' ' . $cache[$record->id]->updated_description;
                $DB->update_record('tool_wsmanager_functions', ['id' => $cacheid] + $data);
            } else {
                $cacheid = $DB->insert_record('tool_wsmanager_functions', ['function_id' => $record->id] + $data);
            }
        }
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
}
