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
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\select;
use lang_string;

/**
 * Class wsmanager_functions
 *
 * @package    tool_wsmanager
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wsmanager_functions extends \core_reportbuilder\local\entities\base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'external_functions',
            'tool_wsmanager_functions',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('functions_extrainfo', 'tool_wsmanager');
    }

    #[\Override]
    public function initialise(): self {
        $tablealias = $this->get_table_alias('tool_wsmanager_functions');

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

        $tablealias = $this->get_table_alias('tool_wsmanager_functions');
        $columns = [];

        // Name column.
        $columns[] = (new column(
            'name',
            new lang_string('description', 'tool_wsmanager'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.description")
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

        $tablealias = $this->get_table_alias('tool_wsmanager_functions');
        $filters = [];

        // All texts filter.
        $filters[] = (new filter(
            text::class,
            'all_texts',
            new lang_string('alltextsfilter', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.all_texts"
        ))
            ->add_joins($this->get_joins());

        // Allowed from ajax filter.
        $filters[] = (new filter(
            boolean_select::class,
            'allowed_from_ajax',
            new lang_string('allowedfromajax', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.allowed_from_ajax"
        ))
            ->add_joins($this->get_joins());

        // Login required filter.
        $filters[] = (new filter(
            boolean_select::class,
            'loginrequired',
            new lang_string('loginrequired', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.loginrequired"
        ))
            ->add_joins($this->get_joins());

        // Type filter.
        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('type', 'tool_wsmanager'),
            $this->get_entity_name(),
            "{$tablealias}.type",
        ))
            ->set_options([
                'read' => new lang_string('type_read', 'tool_wsmanager'),
                'write' => new lang_string('type_write', 'tool_wsmanager'),
            ])
            ->add_joins($this->get_joins());

        return $filters;
    }
}
