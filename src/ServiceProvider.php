<?php

namespace Jimmyjs\ReportGenerator;

use Jimmyjs\ReportGenerator\ReportMedia\CSVReport;
use Jimmyjs\ReportGenerator\ReportMedia\PdfReport;
use Jimmyjs\ReportGenerator\ReportMedia\ExcelReport;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $configPath = __DIR__.'/../config/report-generator.php';
        $this->mergeConfigFrom($configPath, 'report-generator');

        $this->app->bind('pdf.report.generator', function ($app) {
            return new PdfReport ($app);
        });
        $this->app->bind('excel.report.generator', function ($app) {
            return new ExcelReport ($app);
        });
        $this->app->bind('csv.report.generator', function ($app) {
            return new CSVReport ($app);
        });
        $this->app->register('Maatwebsite\Excel\ExcelServiceProvider');

        $this->registerAliases();
	}

	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/views', 'report-generator-view');

		$this->publishes([
	        __DIR__.'/../config/report-generator.php' => config_path('report-generator.php')
	    ], 'config');
	}

	protected function registerAliases()
	{
	    if (class_exists('Illuminate\Foundation\AliasLoader')) {
	        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
	        $loader->alias('PdfReport', \Jimmyjs\ReportGenerator\Facades\PdfReportFacade::class);
	        $loader->alias('ExcelReport', \Jimmyjs\ReportGenerator\Facades\ExcelReportFacade::class);
	        $loader->alias('CSVReport', \Jimmyjs\ReportGenerator\Facades\CSVReportFacade::class);
	    }
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
