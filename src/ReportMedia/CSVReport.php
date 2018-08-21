<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use League\Csv\Writer;
use App, Closure, Exception;
use Jimmyjs\ReportGenerator\ReportGenerator;

class CSVReport extends ReportGenerator
{
    protected $showMeta = false;

    public function download($filename)
    {
        if (!class_exists(Writer::class)) {
            throw new Exception('Please install league/csv to generate CSV Report!');
        }

        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        if ($this->showMeta) {
            foreach ($this->headers['meta'] as $key => $value) {
                $csv->insertOne([$key, $value]);
            }
            $csv->insertOne([' ']);
        }

        $ctr = 1;
        $chunkRecordCount = ($this->limit == null || $this->limit > 50000) ? 50000 : $this->limit + 1;

        if ($this->showHeader) {
            $columns = array_keys($this->columns);
            if (!$this->withoutManipulation && $this->showNumColumn) {
                array_unshift($columns, 'No');
            }
            $csv->insertOne($columns);
        }

        $this->query->chunk($chunkRecordCount, function($results) use(&$ctr, $csv) {
            foreach ($results as $result) {
                if ($this->limit != null && $ctr == $this->limit + 1) return false;
                if ($this->withoutManipulation) {
                    $csv->insertOne($result->toArray());
                } else {
                    $formattedRows = $this->formatRow($result);
                    if ($this->showNumColumn) array_unshift($formattedRows, $ctr);
                    $csv->insertOne($formattedRows);
                }
                $ctr++;
            }

            if ($this->applyFlush) flush();
        });

        $csv->output($filename . '.csv');
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

            array_push($rows, $displayedColValue);
        }

        return $rows;
    }
}