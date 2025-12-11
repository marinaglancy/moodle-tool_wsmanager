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
 * TODO describe file index
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use tool_wsmanager\helper;
use tool_wsmanager\reportbuilder\local\systemreports\ws_list;

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_wsmanager_index', '', null, null, ['nosearch' => true]);

$url = new moodle_url('/admin/tool/wsmanager/index.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);

(new helper())->update_cache_table();

$report = system_report_factory::create(ws_list::class, \context_system::instance(), '', '', 0, []);
$PAGE->requires->js_call_amd('tool_wsmanager/actions', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_wsmanager'));
echo $report->output();

echo $OUTPUT->footer();
