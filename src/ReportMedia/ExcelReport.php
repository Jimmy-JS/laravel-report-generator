<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use App, Closure;
use Jimmyjs\ReportGenerator\ReportGenerator;

class ExcelReport extends ReportGenerator
{
	private $format = 'xlsx';

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

				$sheet->setColumnFormat(['A:Z' => '@']);
		    	$sheet->loadView('report-generator-view::general-excel-template', compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit', 'groupByArr', 'orientation'));
		    });
        })->export($this->format);
	}

	public function simpleDownload($filename)
	{
        return App::make('excel')->create($filename, function($excel) use($filename) {
		    $excel->sheet('Sheet 1', function($sheet) {
				$groupByArr = $this->groupByArr;
				$showTotalColumns = $this->showTotalColumns;
				$sheet->setColumnFormat(['A:Z' => '@']);
				$ctr = 1;
	    		$chunkRecordCount = ($this->limit == null || $this->limit > 1000) ? 1000 : $this->limit;

	    		$sheet->appendRow([$this->headers['title']]);
	    		$sheet->appendRow([' ']);
	    		foreach ($this->headers['meta'] as $key => $value) {
		    		$sheet->appendRow([$key, $value]);
	    		}
	    		$sheet->appendRow([' ']);
	    		$columns = array_keys($this->columns);
	    		array_unshift($columns, 'No');
				$sheet->appendRow($columns);
				$this->query->chunk($chunkRecordCount, function($results) use(&$ctr, $sheet) {
					if ($this->limit != null && $ctr == $this->limit + 1) return false;
					foreach ($results as $result) {
						$formattedRows = $this->formatRow($result);
						array_unshift($formattedRows, $ctr);
						$sheet->appendRow($formattedRows);
						$ctr ++;
					}
				});
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
			array_push($rows, $displayedColValue);
		}

		return $rows;
	}
}