<?php

namespace Jimmyjs\ReportGenerator;

use Config;
use Closure;
use Illuminate\Support\Str;

class ReportGenerator
{
    protected $applyFlush;
    protected $headers;
    protected $columns;
    protected $query;
    protected $limit               = null;
    protected $groupByArr          = [];
    protected $paper               = 'a4';
    protected $orientation         = 'portrait';
    protected $editColumns         = [];
    protected $showNumColumn       = true;
    protected $totalLabel          = 'Grand Total';
    protected $showTotalColumns    = [];
    protected $avgLabel            = 'Average';
    protected $showAvgColumns      = [];
    protected $styles              = [];
    protected $simpleVersion       = false;
    protected $withoutManipulation = false;
    protected $showMeta            = true;
    protected $showHeader          = true;
    // Calculation
    protected $total      = [];
    protected $reportData = [];
    // Example of $reportData = [
    //     1 => [ // Group
    //         [ // Row
    //             'type' => 'row', 'data' => [
    //                 ["colSpan" => 1, "value" => 100], // Column
    //                 ["colSpan" => 1, "value" => 100], // Column
    //                 ["colSpan" => 1, "value" => 100], // Column
    //                 ["colSpan" => 1, "value" => 100], // Column
    //             ],
    //         ], [ // Row
    //             'type' => 'aggregate:total', 'data' => [
    //                 ["colSpan" => 2, "class" => "bg-black", "value" => "Grand Total"], // Column
    //                 ["colSpan" => 1, "class" => "bg-black", "value" => 100], // Column
    //                 ["colSpan" => 1, "class" => "bg-black", "value" => null], // Column
    //             ],
    //         ],
    //     ],
    // ]

    public function __construct ()
    {
        $this->applyFlush = (bool)Config::get('report-generator.flush', true);
    }

    public function of ($title, array $meta, $query, array $columns)
    {
        $this->headers = [
            'title' => $title,
            'meta'  => $meta,
        ];

        $this->query   = $query;
        $this->columns = $this->mapColumns($columns);

        return $this;
    }

    private function mapColumns (array $columns)
    {
        $result = [];

        foreach ($columns as $name => $data) {
            if (is_int($name)) {
                $result[$data] = Str::snake($data);
            } else {
                $result[$name] = $data;
            }
        }

        return $result;
    }

    public function showHeader ($value = true)
    {
        $this->showHeader = $value;

        return $this;
    }

    public function showMeta ($value = true)
    {
        $this->showMeta = $value;

        return $this;
    }

    public function showNumColumn ($value = true)
    {
        $this->showNumColumn = $value;

        return $this;
    }

    public function simple ()
    {
        $this->simpleVersion = true;

        return $this;
    }

    public function withoutManipulation ()
    {
        $this->withoutManipulation = true;

        return $this;
    }

    public function setPaper ($paper)
    {
        $this->paper = strtolower($paper);

        return $this;
    }

    public function editColumns (array $columnNames, array $options)
    {
        foreach ($columnNames as $columnName) {
            $this->editColumn($columnName, $options);
        }

        return $this;
    }

    public function editColumn ($columnName, array $options)
    {
        foreach ($options as $option => $value) {
            $this->editColumns[$columnName][$option] = $value;
        }

        return $this;
    }

    public function totalLabel (string $label)
    {
        $this->totalLabel = $label;

        return $this;
    }

    public function showTotal (array $columns)
    {
        $this->showTotalColumns = $columns;

        return $this;
    }

    public function avgLabel (string $label)
    {
        $this->avgLabel = $label;

        return $this;
    }

    public function showAvg (array $columns)
    {
        $this->showAvgColumns = $columns;

        return $this;
    }

    public function groupBy ($column)
    {
        if (is_array($column)) {
            $this->groupByArr = $column;
        } else {
            array_push($this->groupByArr, $column);
        }

        return $this;
    }

    public function limit ($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOrientation ($orientation)
    {
        $this->orientation = strtolower($orientation);

        return $this;
    }

    public function setCss (array $styles)
    {
        foreach ($styles as $selector => $style) {
            array_push($this->styles, [
                'selector' => $selector,
                'style'    => $style,
            ]);
        }

        return $this;
    }

    protected function generateReportData ()
    {
        $this->reportData = [];
        $this->total      = array_map(function () {
            return 0;
        }, $this->showTotalColumns);

        $currentGroupByData = [];
        $this->newReportGroup();
        foreach ($this->query->take($this->limit ?: null)->cursor() as $rowData) {
            if (count($this->groupByArr) > 0) {
                if (!$this->isOnSameGroup($currentGroupByData, $rowData)) {
                    if (count($this->showTotalColumns) > 0) $this->addAggregateRow('total');
                    if (count($this->showAvgColumns) > 0) $this->addAggregateRow('avg');

                    // Reset total data, Reset Grand Total
                    $this->total = array_map(function () {
                        return 0;
                    }, $this->showTotalColumns);
                    $this->newReportGroup();
                }
            }

            $this->addRow($rowData);
        }

        $groupIdx = count($this->reportData) - 1;
        $hasRow   = $this->getRowCount($groupIdx) > 1;
        if (count($this->showTotalColumns) > 0 && $hasRow) $this->addAggregateRow('total');
        if (count($this->showAvgColumns) > 0 && $hasRow) $this->addAggregateRow('avg');

        return $this->reportData;
    }

    private function newReportGroup (): array
    {
        $groupIdx = array_push($this->reportData, []) - 1;
        return $this->reportData[$groupIdx];
    }

    private function isOnSameGroup (&$currentGroupByData, $rowData): bool
    {
        $isOnSameGroup = true;
        foreach ($this->groupByArr as $groupBy) {
            if (is_object($this->columns[$groupBy]) && $this->columns[$groupBy] instanceof Closure) {
                $groupColData = $this->columns[$groupBy]($rowData);
            } else {
                $groupColData = $rowData->{$this->columns[$groupBy]};
            }

            if (isset($currentGroupByData[$groupBy])) {
                if ($groupColData != $currentGroupByData[$groupBy]) {
                    $isOnSameGroup = false;
                }
            }

            $currentGroupByData[$groupBy] = $groupColData;
        }

        return $isOnSameGroup;
    }

    private function addAggregateRow ($type): void
    {
        $groupIdx         = count($this->reportData) - 1;
        $currentGroupRow  = &$this->newReportRow($groupIdx, "aggregate:{$type}");
        $aggregateColSkip = $this->getAggregateColSkip();

        if ($this->showNumColumn || $aggregateColSkip > 1) {
            // Only show label if first column is not filled
            array_push($currentGroupRow['data'], [
                'colSpan' => $aggregateColSkip, 'class' => "bg-black bold", 'value' => $type === 'avg' ? $this->avgLabel : $this->totalLabel,
            ]);
        }

        $dataFound = false;
        foreach ($this->columns as $colName => $colData) {
            $aggregateColumns = $type === 'avg' ? $this->showAvgColumns : $this->showTotalColumns;
            if (array_key_exists($colName, $aggregateColumns)) {
                $dataFound      = true;
                $format         = $aggregateColumns[$colName];
                $rowCount       = $this->getRowCount($groupIdx);
                $aggregateValue = $type === 'avg' ? round($this->total[$colName] / $rowCount, 2) : $this->total[$colName];

                if ($format instanceof Closure) {
                    array_push($currentGroupRow['data'], [
                        'class' => "bg-black left bold", 'value' => $format($aggregateValue),
                    ]);
                } else if ($format == 'point') {
                    array_push($currentGroupRow['data'], [
                        'class' => "bg-black left bold", 'value' => number_format($aggregateValue, 2),
                    ]);
                } else {
                    array_push($currentGroupRow['data'], [
                        'class' => "bg-black left bold", 'value' => strtoupper($aggregateColumns[$colName]) . ' ' . number_format($aggregateValue, 2),
                    ]);
                }
            } else {
                if ($dataFound) {
                    // Insert blank cell
                    array_push($currentGroupRow['data'], ['class' => "bg-black", 'value' => '']);
                }
            }
        }
    }

    private function &newReportRow ($currentGroupIdx, $rowType = 'row'): array
    {
        $rowIdx = array_push(
                $this->reportData[$currentGroupIdx], [
                    'type' => $rowType, 'data' => [],
                ]
            ) - 1;
        return $this->reportData[$currentGroupIdx][$rowIdx];
    }

    private function getAggregateColSkip (): int
    {
        $aggregateColSkip = 1;
        if (count($this->showTotalColumns) > 0 || count($this->showAvgColumns) > 0) {
            foreach ($this->columns as $colName => $colData) {
                if (!array_key_exists($colName, $this->showTotalColumns) && !array_key_exists($colName, $this->showAvgColumns)) {
                    $aggregateColSkip++;
                } else {
                    break;
                }
            }
        }

        return !$this->showNumColumn ? $aggregateColSkip - 1 : $aggregateColSkip;
    }

    private function getRowCount ($currentGroupIdx): int
    {
        return count(array_filter($this->reportData[$currentGroupIdx], function ($row) {
            return $row['type'] === 'row';
        }));
    }

    private function addRow ($rowData): void
    {
        $groupIdx        = count($this->reportData) - 1;
        $currentGroupRow = &$this->newReportRow($groupIdx);
        $rowCount        = $this->getRowCount($groupIdx);
        if ($this->showNumColumn) {
            array_push($currentGroupRow['data'], ['class' => "left", 'value' => $rowCount + 1]);
        }

        if ($this->withoutManipulation) {
            $this->addRowWithoutManipulation($currentGroupRow, $rowData);
        } else {
            $this->addRowWithManipulation($currentGroupRow, $rowData);
        }
    }

    private function addRowWithoutManipulation (&$currentGroupRow, $rowData)
    {
        $rowDataArr = $rowData->toarray();
        if (count($rowDataArr) > count($this->columns)) array_pop($rowDataArr);
        foreach ($rowDataArr as $cellValue) {
            array_push($currentGroupRow['data'], ['class' => "left", 'value' => $cellValue]);
        }
        foreach ($this->showTotalColumns as $colName => $type) {
            $this->total[$colName] += $rowData->{$this->columns[$colName]};
        }
    }

    private function addRowWithManipulation (&$currentGroupRow, $rowData)
    {
        foreach ($this->columns as $colName => $colData) {
            $class = 'left';
            // Check Edit Column to manipulate class & Data
            if ($colData instanceof Closure) {
                $generatedColValue = $colData($rowData);
            } else {
                $generatedColValue = $rowData->{$colData};
            }
            $displayedColValue = $generatedColValue;
            if (array_key_exists($colName, $this->editColumns)) {
                if (isset($editColumns[$colName]['class'])) {
                    $class = $editColumns[$colName]['class'];
                }

                if (isset($editColumns[$colName]['displayAs'])) {
                    $displayAs = $editColumns[$colName]['displayAs'];
                    if ($displayAs instanceof Closure) {
                        $displayedColValue = $displayAs($rowData);
                    } else {
                        $displayedColValue = $displayAs;
                    }
                }
            }

            if (array_key_exists($colName, $this->showTotalColumns)) {
                $this->total[$colName] += $generatedColValue;
            }
            array_push($currentGroupRow['data'], ['class' => $class, 'value' => $displayedColValue]);
        }
    }
}
