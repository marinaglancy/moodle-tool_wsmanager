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

namespace tool_wsmanager\reportbuilder\local\systemreports;

use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use tool_wsmanager\reportbuilder\local\entities\external_functions;
use tool_wsmanager\reportbuilder\local\entities\wsmanager_functions;
use core_reportbuilder\local\report\action;
use core_tag\reportbuilder\local\entities\tag;
use core_tag_tag;
use core_reportbuilder\local\filters\tags;
use core_reportbuilder\local\report\filter;

/**
 * Class ws_list
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_list extends \core_reportbuilder\system_report {
    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $functionsentity = new external_functions();
        $entitymainalias = $functionsentity->get_table_alias('external_functions');

        $this->set_main_table('external_functions', $entitymainalias);
        $this->add_entity($functionsentity);

        // Join cohort member entity.
        $functionscacheentity = new wsmanager_functions();
        $functionscachealias = $functionscacheentity->get_table_alias('tool_wsmanager_functions');
        $this->add_entity($functionscacheentity
            ->add_join("LEFT JOIN {tool_wsmanager_functions} {$functionscachealias} ON " .
                "{$functionscachealias}.name = {$entitymainalias}.name"));

        // Any columns required by actions should be defined here to ensure they're always available.
        $this->add_base_fields("{$entitymainalias}.name, {$functionscachealias}.parameters_desc, " .
            "{$functionscachealias}.returns_desc");

        // Join tag entity.
        $entitytag = new tag();
        $this->add_entity($entitytag
            ->add_joins($entitytag->get_tag_joins('tool_wsmanager', 'functions', $functionscachealias . '.id')));

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns_from_entity($functionsentity->get_entity_name(), ['name']);
        $this->add_columns_from_entity($functionscacheentity->get_entity_name());

        // Tags column.
        $this->add_column_from_entity('tag:name')
            ->set_title(new lang_string('tags'))
            ->set_aggregation('groupconcat')
            ->set_is_available(core_tag_tag::is_enabled('tool_wsmanager', 'functions') === true);

        $this->add_columns_from_entity($functionsentity->get_entity_name(), ['services']);

        $this->add_filters_from_entity($functionsentity->get_entity_name());
        $this->add_filters_from_entity($functionscacheentity->get_entity_name());

        // Tags filter.
        $this->add_filter((new filter(
            tags::class,
            'tags',
            new lang_string('tags'),
            $functionscacheentity->get_entity_name(),
            "{$functionscachealias}.id",
        ))
            ->set_options([
                'component' => 'tool_wsmanager',
                'itemtype' => 'functions',
            ])
            ->set_is_available(core_tag_tag::is_enabled('tool_wsmanager', 'functions') === true));

        $this->add_actions();

        // Set if report can be downloaded.
        $this->set_downloadable(false);

        $this->set_checkbox_toggleall(static function (stdClass $row): array {
            return [$row->name, $row->name];
        });

        $this->set_initial_sort_column('external_functions:name', SORT_ASC);
        $this->set_default_per_page(12); // To fit on one screen.
    }

    #[\Override]
    protected function can_view(): bool {
        return has_capability('moodle/site:config', \context_system::instance());
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {

        // View details.
        $this->add_action(new action(
            new moodle_url('#'),
            new pix_icon('t/preview', '', 'core'),
            [
                'data-action' => 'tool_wsmanager-view-details',
                'data-function' => ':name',
                'data-parameters-desc' => ':parameters_desc',
                'data-return-desc' => ':returns_desc',
            ],
            false,
            new lang_string('viewdetails', 'tool_wsmanager')
        ));

        // Edit description/tags action.
        $this->add_action(new action(
            new moodle_url('#'),
            new pix_icon('t/edit', '', 'core'),
            ['data-action' => 'tool_wsmanager-edit-description', 'data-function' => ':name'],
            false,
            new lang_string('editdetails', 'tool_wsmanager')
        ));

        // Edit services action.
        $this->add_action(new action(
            new moodle_url('#'),
            new pix_icon('t/edit', '', 'core'),
            ['data-action' => 'tool_wsmanager-edit-services', 'data-function' => ':name'],
            false,
            new lang_string('editservices', 'tool_wsmanager')
        ));
    }
}
