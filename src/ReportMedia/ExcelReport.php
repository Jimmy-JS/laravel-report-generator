<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use App, Closure, Excel;
use Illuminate\Contracts\View\View;
use Jimmyjs\ReportGenerator\ReportGenerator;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class ExportView implements FromView
{
    use Exportable;

    /**
     * @var View
     */
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function view(): View
    {
        return $this->view;
    }

}

class ExcelReport extends ReportGenerator
{
    private $format = 'xlsx';
    private $total  = [];

    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function make()
    {
        $headers          = $this->headers;
        $query            = $this->query;
        $columns          = $this->columns;
        $limit            = $this->limit;
        $groupByArr       = $this->groupByArr;
        $orientation      = $this->orientation;
        $editColumns      = $this->editColumns;
        $showTotalColumns = $this->showTotalColumns;
        $styles           = $this->styles;
        $showHeader       = $this->showHeader;
        $showMeta         = $this->showMeta;
        $applyFlush       = $this->applyFlush;
        $showNumColumn    = $this->showNumColumn;

        if ($this->withoutManipulation) {
            $view = view('laravel-report-generator::without-manipulation-excel-template', compact('headers', 'columns', 'showTotalColumns', 'query', 'limit', 'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn'));
        } else {
            $view = view('laravel-report-generator::general-excel-template', compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit', 'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn'));
        }

        return new ExportView($view);
    }

    public function download($filename)
    {
        $export = $this->make();
        return Excel::download($export, "{$filename}.{$this->format}");
    }

    public function simpleDownload($filename)
    {
        return $this->download($filename);
    }

    private function formatRow($result)
    {
        $rows = [];
        foreach ($this->columns as $colName => $colData) {
            if (is_object($colData) && $colData instanceof Closure) {
                $generatedColData = $colData($result);
            } else {
                $generatedColData = $result->$colData;
            }
            $displayedColValue = $generatedColData;
            if (array_key_exists($colName, $this->editColumns)) {
                if (isset($this->editColumns[$colName]['displayAs'])) {
                    $displayAs = $this->editColumns[$colName]['displayAs'];
                    if (is_object($displayAs) && $displayAs instanceof Closure) {
                        $displayedColValue = $displayAs($result);
                    } elseif (!(is_object($displayAs) && $displayAs instanceof Closure)) {
                        $displayedColValue = $displayAs;
                    }
                }
            }

            if (array_key_exists($colName, $this->showTotalColumns)) {
                $this->total[$colName] += $generatedColData;
            }

            array_push($rows, $displayedColValue);
        }

        return $rows;
    }
}
