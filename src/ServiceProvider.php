<?php

namespace Jimmyjs\ReportGenerator;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Jimmyjs\ReportGenerator\ReportMedia\ExcelReport;
use Jimmyjs\ReportGenerator\ReportMedia\PdfReport;

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
        $this->app->singleton('excel.report.generator', function ($app) {
            return new ExcelReport($app);
        });
        $this->app->singleton('pdf.report.generator', function ($app) {
            return new PdfReport($app);
        });
        $this->app->register('Maatwebsite\Excel\ExcelServiceProvider');

        $this->registerAliases();
	}

	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/views', 'report-generator-view');
	}

	protected function registerAliases()
	{
	    if (class_exists('Illuminate\Foundation\AliasLoader')) {
	        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
	        $loader->alias('ExcelReport', \Jimmyjs\ReportGenerator\Facades\ExcelReportFacade::class);
	        $loader->alias('PdfReport', \Jimmyjs\ReportGenerator\Facades\PdfReportFacade::class);
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
