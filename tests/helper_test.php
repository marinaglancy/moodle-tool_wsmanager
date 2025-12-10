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
 * Tests for Web service manager
 *
 * @package    tool_wsmanager
 * @category   test
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class helper_test extends \advanced_testcase {
    public function test_update_cache_table(): void {
        global $DB;

        $this->resetAfterTest();

        // Initially the cache table is empty.
        $count = $DB->count_records('tool_wsmanager_functions');
        $this->assertEquals(0, $count);

        $helper = new helper();
        $helper->update_cache_table();

        // After updating the cache table it should contain some records.
        $count = $DB->count_records('tool_wsmanager_functions');
        $countfunc = $DB->count_records('external_functions');
        $this->assertEquals($countfunc, $count);
    }
}
