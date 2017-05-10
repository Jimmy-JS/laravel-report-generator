<?php

namespace Jimmyjs\ReportGenerator;

class ReportGenerator 
{
	protected $media = 'pdf';
	protected $headers;
	protected $columns;
	protected $query;
	protected $limit = null;
	protected $groupByArr = [];
	protected $paper = 'a4';
	protected $orientation = 'portrait';
	protected $editColumns = [];
	protected $showTotalColumns = [];
	protected $styles = [];

	public function media($media)
	{
		$this->media = strtolower($media);

		return $this;
	}

	public function of($title, Array $meta = [], $query, Array $columns)
	{
		$this->headers = [
			'title' => $title,
			'meta'  => $meta
		];

		$this->query = $query;
		$this->columns = $columns;

		return $this;
	}

	public function setPaper($paper)
	{
		$this->paper = strtolower($paper);

		return $this;
	}

	public function editColumn($columnName, Array $option)
	{
		$this->editColumns[$columnName] = $option;

		return $this;
	}

	public function showTotal(Array $columns)
	{
		$this->showTotalColumns = $columns;

		return $this;
	}

	public function groupBy($column)
	{
		if (is_array($column)) {
			$this->groupByArr = $column;
		} else {
			array_push($this->groupByArr, $column);
		}

		return $this;
	}

	public function limit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	public function setOrientation($orientation)
	{
		$this->orientation = strtolower($orientation);

		return $this;
	}

	public function setCss(Array $styles)
	{
		foreach ($styles as $selector => $style) {
			array_push($this->styles, [
				'selector' => $selector,
				'style' => $style
			]);
		}

		return $this;
	}
}