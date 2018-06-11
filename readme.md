# Laravel Report Generators (PDF & Excel)
Rapidly Generate Simple Pdf Report on Laravel (Using [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) or [barryvdh/laravel-snappy](https://github.com/barryvdh/laravel-snappy)) or Excel Report (using [Maatwebsite/Laravel-Excel](https://github.com/Maatwebsite/Laravel-Excel))

This package provides a simple pdf & excel report generators to speed up your workflow

## Installation
Add package to your composer:

    composer require jimmyjs/laravel-report-generator

Then, add the ServiceProvider to the providers array in config/app.php

    Jimmyjs\ReportGenerator\ServiceProvider::class,

**Optionally**, you can add this to your aliases array in config/app.php 

    'PdfReport' => Jimmyjs\ReportGenerator\Facades\PdfReportFacade::class,
    'ExcelReport' => Jimmyjs\ReportGenerator\Facades\ExcelReportFacade::class,
    'CSVReport' => Jimmyjs\ReportGenerator\Facades\CSVReportFacade::class,

## Usage
This package is make use of `chunk` method (Eloquent / Query Builder) so it can handle big data without memory exhausted.

Also, You can use `PdfReport`, `ExcelReport` or `CSVReport` facade for shorter code that already registered as an alias.

### Example Display PDF Code
```php
// PdfReport Aliases
use PdfReport;

public function displayReport(Request $request) {
    // Retrieve any filters
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $sortBy = $request->input('sort_by');

    // Report title
    $title = 'Registered User Report';

    // For displaying filters description on header
    $meta = [
        'Registered on' => $fromDate . ' To ' . $toDate,
        'Sort By' => $sortBy
    ];

    // Do some querying..
    $queryBuilder = User::select(['name', 'balance', 'registered_at'])
                        ->whereBetween('registered_at', [$fromDate, $toDate])
                        ->orderBy($sortBy);

    // Set Column to be displayed
    $columns = [
        'Name' => 'name',
        'Registered At', // if no column_name specified, this will automatically seach for snake_case of column name (will be registered_at) column from query result
        'Total Balance' => 'balance',
        'Status' => function($result) { // You can do if statement or any action do you want inside this closure
            return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
        }
    ];

    /*
        Generate Report with flexibility to manipulate column class even manipulate column value (using Carbon, etc).

        - of()         : Init the title, meta (filters description to show), query, column (to be shown)
        - editColumn() : To Change column class or manipulate its data for displaying to report
        - editColumns(): Mass edit column
        - showTotal()  : Used to sum all value on specified column on the last table (except using groupBy method). 'point' is a type for displaying total with a thousand separator
        - groupBy()    : Show total of value on specific group. Used with showTotal() enabled.
        - limit()      : Limit record to be showed
        - make()       : Will producing DomPDF / SnappyPdf instance so you could do any other DomPDF / snappyPdf method such as stream() or download()
    */
    return PdfReport::of($title, $meta, $queryBuilder, $columns)
                    ->editColumn('Registered At', [
                        'displayAs' => function($result) {
                            return $result->registered_at->format('d M Y');
                        }
                    ])
                    ->editColumn('Total Balance', [
                        'displayAs' => function($result) {
                            return thousandSeparator($result->balance);
                        }
                    ])
                    ->editColumns(['Total Balance', 'Status'], [
                        'class' => 'right bold'
                    ])
                    ->showTotal([
                        'Total Balance' => 'point' // if you want to show dollar sign ($) then use 'Total Balance' => '$'
                    ])
                    ->limit(20)
                    ->stream(); // or download('filename here..') to download pdf
}
```

Note: For downloading to excel, just change `PdfReport` facade to `ExcelReport` facade no more modifications

### Data Manipulation
```php
$columns = [
    'Name' => 'name',
    'Registered At' => 'registered_at',
    'Total Balance' => 'balance',
    'Status' => function($result) { // You can do data manipulation, if statement or any action do you want inside this closure
        return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
    }
];
```
Will produce a same result with:
```php
$columns = [
    'Name' => function($result) {
        return $result->name;
    },
    'Registered At' => function($result) {
        return $result->registered_at;
    },
    'Total Balance' => function($result) {
        return $result->balance;
    },
    'Status' => function($result) { // You can do if statement or any action do you want inside this closure
        return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
    }
];
```
So you can do some **eager loading relation** like:

```php
$post = Post::with('comment')->where('active', 1);

$columns = [
    'Post Title' => function($result) {
        return $result->title;
    },
    'Slug' => 'slug',
    'Top Comment' => function($result) {
        return $result->comment->body;
    }
];
```
### Output Report
![Output Report with Grand Total](https://raw.githubusercontent.com/Jimmy-JS/laravel-report-generator/master/screenshots/report-with-total.png)


### Example Code With Group By
Or, you can total all records by group using `groupBy` method
```php
    ...
    // Do some querying..
    $queryBuilder = User::select(['name', 'balance', 'registered_at'])
                        ->whereBetween('registered_at', [$fromDate, $toDate])
                        ->orderBy('registered_at', 'ASC'); // You should sort groupBy column to use groupBy() Method

    // Set Column to be displayed
    $columns = [
        'Registered At' => 'registered_at',
        'Name' => 'name',
        'Total Balance' => 'balance',
        'Status' => function($result) { // You can do if statement or any action do you want inside this closure
            return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
        }
    ];
    return PdfReport::of($title, $meta, $queryBuilder, $columns)
                    ->editColumn('Registered At', [
                        'displayAs' => function($result) {
                            return $result->registered_at->format('d M Y');
                        }
                    ])
                    ->editColumn('Total Balance', [
                        'class' => 'right bold',
                        'displayAs' => function($result) {
                            return thousandSeparator($result->balance);
                        }
                    ])
                    ->editColumn('Status', [
                        'class' => 'right bold',
                    ])
                    ->groupBy('Registered At')
                    ->showTotal([
                        'Total Balance' => 'point'
                    ])
                    ->stream();
```

**PLEASE TAKE NOTE TO SORT GROUPBY COLUMN VIA QUERY FIRST TO USE THIS GROUP BY METHOD.**

### Output Report With Group By Registered At
![Output Report with Group By Grand Total](https://raw.githubusercontent.com/Jimmy-JS/laravel-report-generator/master/screenshots/report-with-group-by.png)


## Other Method

### 1. setPaper($paper = 'a4')
**Supported Media Type**: PDF

**Description**: Set Paper Size

**Params**:
* $paper (Default: 'a4')

**Usage:**
```php
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->setPaper('a6')
         ->make();
```

### 2. setCss(Array $styles)
**Supported Media Type**: PDF, Excel

**Description**: Set a new custom styles with given selector and style to apply

**Params**:
* Array $styles (Key: $selector, Value: $style)

**Usage:**
```php
ExcelReport::of($title, $meta, $queryBuilder, $columns)
            ->editColumn('Registered At', [
                'class' => 'right bolder italic-red'
            ])
            ->setCss([
                '.bolder' => 'font-weight: 800;',
                '.italic-red' => 'color: red;font-style: italic;'
            ])
            ->make();
```

### 3. setOrientation($orientation = 'portrait')
**Supported Media Type**: PDF

**Description**: Set Orientation to Landscape or Portrait

**Params**:
* $orientation (Default: 'portrait')

**Usage:**
```php
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->setOrientation('landscape')
         ->make();
```

### 4. withoutManipulation()
**Supported Media Type**: PDF, Excel, CSV

**Description**: Faster generating report, but all columns properties must be matched the selected column from SQL Queries

**Usage:**
```php
$queryBuilder = Customer::select(['name', 'age'])->get();
$columns = ['Name', 'Age'];
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->withoutManipulation()
         ->make();
```

### 5. showMeta($value = true)
**Supported Media Type**: PDF, Excel, CSV

**Description**: Show / hide meta attribute on report

**Params**:
* $value (Default: true)

**Usage:**
```php
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->showMeta(false) // Hide meta
         ->make();
```

### 6. showHeader($value = true)
**Supported Media Type**: PDF, Excel, CSV

**Description**: Show / hide column header on report

**Params**:
* $value (Default: true)

**Usage:**
```php
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->showHeader(false) // Hide column header
         ->make();
```
