<?php

namespace Jimmyjs\ReportGenerator\ReportMedia;

use Config;
use Jimmyjs\ReportGenerator\ReportGenerator;

class PdfReport extends ReportGenerator
{
	public function make()
	{
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
		$showNumColumn = $this->showNumColumn;
		$applyFlush = $this->applyFlush;

		if ($this->withoutManipulation) {
			$html = \View::make('laravel-report-generator::without-manipulation-pdf-template', compact('headers', 'columns', 'showTotalColumns', 'query', 'limit', 'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn'))->render();
		} else {
			$html = \View::make('laravel-report-generator::general-pdf-template', compact('headers', 'columns', 'editColumns', 'showTotalColumns', 'styles', 'query', 'limit', 'groupByArr', 'orientation', 'showHeader', 'showMeta', 'applyFlush', 'showNumColumn'))->render();
		}

		$pdfLibrary = Config::get('report-generator.pdfLibrary', 'snappy');
		if ($pdfLibrary === 'snappy') {
			$pdf = \App::make('snappy.pdf.wrapper');
			$pdf->setOption('footer-font-size', 10);
			$pdf->setOption('footer-left', __('laravel-report-generator::messages.page'));
			$pdf->setOption('footer-right', __('laravel-report-generator::messages.printed_at', ['date' => date('d M Y H:i:s')]));
		} else if ($pdfLibrary === 'dompdf') {
			try {
				$pdf = \App::make('dompdf.wrapper');
			} catch (\ReflectionException $e) {
				throw new \Exception(__('laravel-report-generator::exceptions.pdf_not_found'));
			}
		}

		return $pdf->loadHTML($html)->setPaper($this->paper, $orientation);
	}

	public function stream()
	{
		return $this->make()->stream();
	}

	public function download($filename)
	{
		return $this->make()->download($filename . '.pdf');
	}

	public function store($filePath)
	{
      $content = $this->make()->download()->getOriginalContent();

      \Storage::put($filePath, $content);
	}
}
