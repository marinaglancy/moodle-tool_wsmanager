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

namespace tool_wsmanager\reportbuilder\local\entities;

use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\database;
use lang_string;
use tool_wsmanager\reportbuilder\local\filters\service_filter;

/**
 * Class external_functions
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_functions extends \core_reportbuilder\local\entities\base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'external_functions',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('external_functions', 'tool_wsmanager');
    }

    #[\Override]
    public function initialise(): self {
        $tablealias = $this->get_table_alias('external_functions');

        $columns = array_merge($this->get_all_columns(), []);
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = array_merge($this->get_all_filters(), []);
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }


    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;

        $tablealias = $this->get_table_alias('external_functions');
        $columns = [];

        // Name column.
        $columns[] = (new column(
            'name',
            new lang_string('functionname', 'tool_wsmanager'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.name")
            ->set_is_sortable(true);

        // Component column.
        $columns[] = (new column(
            'component',
            new lang_string('component', 'tool_wsmanager'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.component")
            ->set_is_sortable(true);

        // Services column.
        $sfalias = database::generate_alias();
        $salias = database::generate_alias();
        $sqlgroupconcat = $DB->sql_group_concat($DB->sql_concat($salias . '.id', "':'", $salias . '.name'), '<br>');
        $columns[] = (new column(
            'services',
            new lang_string('services', 'tool_wsmanager'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("(SELECT $sqlgroupconcat
                FROM {external_services_functions} $sfalias, {external_services} $salias
                WHERE {$tablealias}.name = $sfalias.functionname AND $salias.id = $sfalias.externalserviceid)", 'services')
            ->set_is_sortable(true);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $tablealias = $this->get_table_alias('external_functions');
        $filters = [];

        // Name filter.
        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('functionname', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.name"
        ))
            ->add_joins($this->get_joins());

        // Component filter.
        $filters[] = (new filter(
            text::class,
            'component',
            new lang_string('component', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.component"
        ))
            ->add_joins($this->get_joins());

        // Service filter.
        $filters[] = (new filter(
            service_filter::class,
            'service',
            new lang_string('services', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.name"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
