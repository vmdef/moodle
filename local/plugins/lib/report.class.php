<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @property-read array $columns
 * @property-read array $data
 * @property-read array $totalrows
 * @property-read html_table $html_table
 * @property-read string $report_description
 * @property-read string $report_title
 * @property-read moodle_url $url
 */
abstract class local_plugins_report_base extends local_plugins_class_base implements renderable {

    const PAGE_VAR = 'p';
    const PERPAGE_VAR = 'l';
    const SORT_VAR = 's';
    const SORTDIR_VAR = 'd';

    protected $name = null;
    protected $columns = null;
    protected $data = null;
    protected $pagingbar = null;

    protected $page = null;
    protected $perpage = null;
    protected $sort = null;
    protected $sortdir = 'DESC';
    protected $totalrows = null;

    protected $defaultsort = 'id';

    abstract protected function define_columns();
    abstract protected function fetch_data();
    abstract protected function get_report_title();
    abstract protected function get_report_description();
    abstract public function can_view();
    abstract protected function fetch_count();

    final public function __construct($name) {
        $this->name = $name;
        $this->page =    optional_param(self::PAGE_VAR, 0, PARAM_INT);
        $this->perpage = optional_param(self::PERPAGE_VAR, 50, PARAM_INT);
        $this->sort =    optional_param(self::SORT_VAR, $this->defaultsort, PARAM_ALPHANUMEXT);
        $this->sortdir = optional_param(self::SORTDIR_VAR, 'DESC', PARAM_ALPHA);
        if ($this->sortdir !== 'ASC') {
            $this->sortdir == 'DESC';
        }
    }

    final public function get_columns() {
        if ($this->columns === null) {
            $this->columns = $this->define_columns();
        }
        return $this->columns;
    }

    final public function get_data() {
        if ($this->data === null) {
            $this->data = $this->fetch_data();
        }
        return $this->data;
    }

    final public function get_html_table() {
        $columns = $this->get_columns();
        $data = $this->get_data();

        $keys = array();

        $table = new html_table();
        $table->id = $this->name;
        $table->attributes = array('class' => 'generaltable plugins-report');
        foreach ($columns as $column) {
            $column->update_table($table);
            $keys[] = $column->field;
        }
        $table->data = array();
        foreach ($data as $item) {
            $row = new html_table_row();
            foreach ($keys as $key) {
                $row->cells[] = new html_table_cell($item->{$key});
            }
            $table->data[] = $row;
        }

        return $table;
    }

    final public function get_baseurl() {
        return new local_plugins_url('/local/plugins/report/index.php', array(
            'report' => $this->name
        ));
    }

    final public function get_url() {
        return new local_plugins_url('/local/plugins/report/index.php', array(
            'report' => $this->name,
            self::PAGE_VAR => $this->page,
            self::PERPAGE_VAR => $this->perpage,
            self::SORT_VAR => $this->sort,
            self::SORTDIR_VAR => $this->sortdir,
        ));
    }

    final public function get_pagingbar() {
        if ($this->pagingbar === null) {
            $this->pagingbar = new paging_bar($this->get_totalrows(), $this->page, $this->perpage, $this->get_url(), self::PAGE_VAR);
        }
        return $this->pagingbar;
    }

    final public function get_totalrows() {
        if ($this->totalrows === null) {
            $this->totalrows = $this->fetch_count();
        }
        return $this->totalrows;
    }

    final public function requires_paging() {
        return $this->get_totalrows() > $this->perpage;
    }

    /**
     * Can the current user see the report in the given context?
     *
     * @return bool
     */
    final public function user_can_view($context) {

        if (!has_capability(local_plugins::CAP_VIEWREPORTS, $context)) {
            return false;
        }

        return $this->can_view();
    }

    public function requires_login() {
        return true;
    }

    /**
     * Should the report be displayed in the list of reports at the front page
     *
     * Note that this quick access area displays a report only if it actually
     * has some data to report.
     *
     * @return bool
     */
    public function quick_access() {
        return false;
    }
}

class local_plugins_report_column extends local_plugins_class_base {

    protected $report;

    protected $field;
    protected $text;
    protected $title;
    protected $sortable = false;
    protected $span = 1;
    protected $classes = array();

    public function __construct(local_plugins_report_base $report, $field, $text, $title = null, $sortable = false, $span = 1, array $classes = null) {
        $this->report = $report;
        $this->field = preg_replace('#[^a-zA-Z0-9\-]+#', '_', $field);
        $this->text = $text;
        if (!empty($title)) {
            $this->title = $text;
        }
        $this->sortable = (bool)$sortable;
        if ($this->span > 1) {
            $this->span = $span;
        }
        if (!empty($classes)) {
            $this->classes = $classes;
        }
        $this->classes[] = 'column-'.$this->field;
    }

    public function update_table(html_table $table) {
        if (!is_array($table->head)) {
            $table->head = array();
            $table->headspan = array();
            $table->colclasses = array();
        }
        $properties = array();
        if (!empty($this->title)) {
            $properties['title'] = $this->title;
        }
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->attributes = array();
        if ($this->sortable) {
            $cell->attributes['class'] = 'sortable';
            $url = $this->report->url;
            if ($url->param(local_plugins_report_base::SORT_VAR) == $this->field) {
                $cell->attributes['class'] .= ' current-sort';
                if ($url->param(local_plugins_report_base::SORTDIR_VAR) == 'ASC') {
                    $cell->attributes['class'] .= ' current-sort-asc';
                    $url->param(local_plugins_report_base::SORTDIR_VAR, 'DESC');
                } else {
                    $cell->attributes['class'] .= ' current-sort-desc';
                    $url->param(local_plugins_report_base::SORTDIR_VAR, 'ASC');
                }
            }
            $url->param(local_plugins_report_base::SORT_VAR, $this->field);
            $cell->text = html_writer::link($url, $this->text, $properties);
        } else {
            $cell->attributes['class'] = 'not-sortable';
            $cell->text = html_writer::tag('span', $this->text, $properties);
        }

        $table->head[] = $cell;
        $table->colclasses[] = join(' ', $this->classes);
        $table->headspan[] = $this->span;
    }
}