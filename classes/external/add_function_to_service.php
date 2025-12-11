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

namespace tool_wsmanager\external;

use context_system;
use core\exception\moodle_exception;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_api;
use core_external\external_value;

/**
 * Implementation of web service tool_wsmanager_add_function_to_service
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_function_to_service extends external_api {
    /**
     * Describes the parameters for tool_wsmanager_add_function_to_service
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'functionnames' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Function name'),
                'List of external functions names',
                VALUE_REQUIRED
            ),
            'serviceid' => new external_value(PARAM_INT, 'Service id', VALUE_REQUIRED),
            'action' => new external_value(PARAM_ALPHA, 'Action to perform - add or remove', VALUE_DEFAULT, 'add'),
        ]);
    }

    /**
     * Implementation of web service tool_wsmanager_add_function_to_service
     *
     * @param array $functionnames
     * @param int $serviceid
     * @param string $action
     */
    public static function execute($functionnames, $serviceid, $action) {
        global $DB;
        // Parameter validation.
        ['functionnames' => $functionnames, 'serviceid' => $serviceid, 'action' => $action] = self::validate_parameters(
            self::execute_parameters(),
            ['functionnames' => $functionnames, 'serviceid' => $serviceid, 'action' => $action]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);
        (new \tool_wsmanager\helper())->add_function_to_service($functionnames, $serviceid, $action);
    }

    /**
     * Describe the return structure for tool_wsmanager_add_function_to_service
     */
    public static function execute_returns() {
        return null;
    }
}
