# Laravel Report Generators (PDF & Excel)
Rapidly Generate Simple Pdf Report on Laravel (Using [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) or [barryvdh/laravel-snappy](https://github.com/barryvdh/laravel-snappy)) or Excel Report (using [Maatwebsite/Laravel-Excel](https://github.com/Maatwebsite/Laravel-Excel))

This package provides a simple pdf & excel report generators to speed up your workflow

## Installation
Add package to your composer:

    composer require jimmyjs/laravel-report-generator

Then, add the ServiceProvider to the providers array in config/app.php

    Jimmyjs\ReportGenerator\ServiceProvider::class,

For more better speed on generating pdf report, I recommend you to use laravel snappy package. To using laravel snappy, you should install `wkhtmltopdf` to work with this package [(Jump to wkhtmltopdf installation)](#wkhtmltopdf-installation)

**Optionally**, you can add this to your aliases array in config/app.php

    'PdfReport' => Jimmyjs\ReportGenerator\Facades\PdfReportFacade::class,
    'ExcelReport' => Jimmyjs\ReportGenerator\Facades\ExcelReportFacade::class,
    'CSVReport' => Jimmyjs\ReportGenerator\Facades\CSVReportFacade::class,

## Usage
This package is make use of `chunk` method (Eloquent / Query Builder) so it can handle big data without memory exhausted.

Also, You can use `PdfReport`, `ExcelReport` or `CSVReport` facade for shorter code that already registered as an alias.

### Example Display PDF Code
```php
use PdfReport;

public function displayReport(Request $request)
{
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $sortBy = $request->input('sort_by');

    $title = 'Registered User Report'; // Report title

    $meta = [ // For displaying filters description on header
        'Registered on' => $fromDate . ' To ' . $toDate,
        'Sort By' => $sortBy
    ];

    $queryBuilder = User::select(['name', 'balance', 'registered_at']) // Do some querying..
                        ->whereBetween('registered_at', [$fromDate, $toDate])
                        ->orderBy($sortBy);

    $columns = [ // Set Column to be displayed
        'Name' => 'name',
        'Registered At', // if no column_name specified, this will automatically seach for snake_case of column name (will be registered_at) column from query result
        'Total Balance' => 'balance',
        'Status' => function($result) { // You can do if statement or any action do you want inside this closure
            return ($result->balance > 100000) ? 'Rich Man' : 'Normal Guy';
        }
    ];

    // Generate Report with flexibility to manipulate column class even manipulate column value (using Carbon, etc).
    return PdfReport::of($title, $meta, $queryBuilder, $columns)
                    ->editColumn('Registered At', [ // Change column class or manipulate its data for displaying to report
                        'displayAs' => function($result) {
                            return $result->registered_at->format('d M Y');
                        },
                        'class' => 'left'
                    ])
                    ->editColumns(['Total Balance', 'Status'], [ // Mass edit column
                        'class' => 'right bold'
                    ])
                    ->showTotal([ // Used to sum all value on specified column on the last table (except using groupBy method). 'point' is a type for displaying total with a thousand separator
                        'Total Balance' => 'point' // if you want to show dollar sign ($) then use 'Total Balance' => '$'
                    ])
                    ->limit(20) // Limit record to be showed
                    ->stream(); // other available method: download('filename') to download pdf / make() that will producing DomPDF / SnappyPdf instance so you could do any other DomPDF / snappyPdf method such as stream() or download()
}
```

Note: For downloading to excel / CSV, just change `PdfReport` facade to `ExcelReport` / `CSVReport` facade with no more modifications

### Data Manipulation
```php
$columns = [
    'Name' => 'name',
    'Registered At' => 'registered_at',
    'Total Balance' => 'balance',
    'Status' => function($customer) { // You can do data manipulation, if statement or any action do you want inside this closure
        return ($customer->balance > 100000) ? 'Rich Man' : 'Normal Guy';
    }
];
```
Will produce a same result with:
```php
$columns = [
    'Name' => function($customer) {
        return $customer->name;
    },
    'Registered At' => function($customer) {
        return $customer->registered_at;
    },
    'Total Balance' => function($customer) {
        return $customer->balance;
    },
    'Status' => function($customer) { // You can do if statement or any action do you want inside this closure
        return ($customer->balance > 100000) ? 'Rich Man' : 'Normal Guy';
    }
];
```
### Report Output
![Report Output with Grand Total](https://raw.githubusercontent.com/Jimmy-JS/laravel-report-generator/master/screenshots/report-with-total.png)

With this manipulation, you could do some **eager loading relation** like:
```php
$post = Post::with('comments')->where('active', 1);

$columns = [
    'Post Title' => function($post) {
        return $post->title;
    },
    'Slug' => 'slug',
    'Latest Comment' => function($post) {
        return $post->comments->first()->body;
    }
];
```

### Example Code With Group By
Or, you can total all records by group using `groupBy` method
```php
    ...
    // Do some querying..
    $queryBuilder = User::select(['name', 'balance', 'registered_at'])
                        ->whereBetween('registered_at', [$fromDate, $toDate])
                        ->orderBy('registered_at', 'ASC'); // You should sort groupBy column to use groupBy() Method

    $columns = [ // Set Column to be displayed
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
                    ->groupBy('Registered At') // Show total of value on specific group. Used with showTotal() enabled.
                    ->showTotal([
                        'Total Balance' => 'point'
                    ])
                    ->stream();
```

**PLEASE TAKE NOTE TO SORT GROUPBY COLUMN VIA QUERY FIRST TO USE THIS GROUP BY METHOD.**

### Output Report With Group By Registered At
![Output Report with Group By Grand Total](https://raw.githubusercontent.com/Jimmy-JS/laravel-report-generator/master/screenshots/report-with-group-by.png)


## Wkhtmltopdf Installation
* Download wkhtmltopdf from https://wkhtmltopdf.org/downloads.html
* Change your snappy config located in `/config/snappy.php` (run `php artisan vendor:publish` if `snappy.php` file is not created) to:
```
    'pdf' => array(
        'enabled' => true,
        'binary'  => '/usr/local/bin/wkhtmltopdf', // Or specified your custom wkhtmltopdf path
        'timeout' => false,
        'options' => array(),
        'env'     => array(),
    ),
```


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

### 7. showNumColumn($value = true)
**Supported Media Type**: PDF, Excel, CSV

**Description**: Show / hide number column on report

**Params**:
* $value (Default: true)

**Usage:**
```php
PdfReport::of($title, $meta, $queryBuilder, $columns)
         ->showNumColumn(false) // Hide number column
         ->make();
```

### 8. simple()
**Supported Media Type**: Excel

**Description**: Generate excel in simple mode (no styling on generated excel report, but faster in generating report)

**Params**:
* None

**Usage:**
```php
ExcelReport::of($title, $meta, $queryBuilder, $columns)
         ->simple()
         ->download('filename');
```
