<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use App, Closure;
use Jimmyjs\ReportGenerator\ReportGenerator;

class ExcelReport extends ReportGenerator
{
	private $format = 'xlsx';
	private $total = [];

	public function setFormat($format)
	{
		$this->format = $format;

		return $this;
	}

	public function download($filename)
	{
		if ($this->simpleVersion) return $this->simpleDownload($filename);

		return App::make('excel')->create($filename, function($excel) use($filename) {
		    $excel->sheet('Sheet 1', function($sheet) {
				$headers = $this->headers;
				$query = $this->query;
				$columns = $this->columns;
				$limit = $this->limit;
				$groupByArr = $this->groupByArr;
				$orientation = $this->orientation;
				$editColumns = $this->editColumns;
				$showTotalColumns = $this->showTotalColumns;
				$styles = $this->styles;
				$showHeader = $this->showHeader;
				$showMeta = $this->showMeta;
				$applyFlush = $this->applyFlush;

				$sheet->setColumnFormat(['A:Z' => '@']);

				if ($this->withoutManipulation) {
			    	$sheet->loadView('report-generator-view::without-manipulation-excel-template', compact('headers', 'columns', 'showTotalColumns', 'query', 'limit', 'orientation', 'showHeader', 'showMeta', 'applyFlush'));
			    } else {
			    	$sheet->loadView('report-generator-view::general-excel-template', compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit', 'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush'));
			    }
		    });
        })->export($this->format);
	}

	public function simpleDownload($filename)
	{
        return App::make('excel')->create($filename, function($excel) use($filename) {
		    $excel->sheet('Sheet 1', function($sheet) {
				$sheet->setColumnFormat(['A:Z' => '@']);
				$ctr = 1;
				foreach ($this->showTotalColumns as $column => $type) {
					$this->total[$column] = 0;
				}

	    		$chunkRecordCount = ($this->limit == null || $this->limit > 50000) ? 50000 : $this->limit + 1;

	    		$sheet->appendRow([$this->headers['title']]);
	    		$sheet->appendRow([' ']);

	    		if ($this->showMeta) {
		    		foreach ($this->headers['meta'] as $key => $value) {
			    		$sheet->appendRow([$key, $value]);
		    		}
		    		$sheet->appendRow([' ']);
		    	}

		    	if ($this->showHeader) {
		    		$columns = array_keys($this->columns);
		    		if (!$this->withoutManipulation) {
			    		array_unshift($columns, 'No');
			    	}
					$sheet->appendRow($columns);
				}

				$this->query->chunk($chunkRecordCount, function($results) use(&$ctr, $sheet) {
					foreach ($results as $result) {
		                if ($this->limit != null && $ctr == $this->limit + 1) return false;
		                if ($this->withoutManipulation) {
		                    $sheet->appendRow($result->toArray());
		                } else {
		                    $formattedRows = $this->formatRow($result);
		                    array_unshift($formattedRows, $ctr);
		                    $sheet->appendRow($formattedRows);
		                }
		                $ctr++;
					}

					if ($this->applyFlush) flush();
				});

				if ($this->showTotalColumns) {
					$totalRows = collect(['Grand Total']);
					array_shift($columns);
					foreach ($columns as $columnName) {
						if (array_key_exists($columnName, $this->showTotalColumns)) {
							if ($this->showTotalColumns[$columnName] == 'point') {
								$totalRows->push(number_format($this->total[$columnName], 0, '.', ','));
							} else {
								$totalRows->push(strtoupper($this->showTotalColumns[$columnName]) . ' ' . number_format($this->total[$columnName], 0, '.', ','));
							}
						} else {
							$totalRows->push(null);
						}
					}
					$sheet->appendRow($totalRows->toArray());
				}
		    });
        })->export($this->format);
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