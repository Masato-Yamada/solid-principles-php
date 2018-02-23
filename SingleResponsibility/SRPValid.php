<?php

interface SalesOutputInterface {
    public function output();
}
class HtmlOutput implements SalesOutputInterface {
    public function output($sales)
    {
        echo "<h1>your sales:¥{$sales}</h1>";
    }
}

interface SalesRepositoryInterface {
    public function between();
}
class SalesDbRepository implements SalesRepositoryInterface{
    public function between($startDate, $endDate)
    {
        return DB::table('sales')->whereBetween('create_at', [$startDate, $endDate])->sum('amount');
    }
}

class SalesReporter {
    public $salesRepository;
    public function __construct(SalesRepositoryInterface $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }
    public function getSalesBetween($startDate, $endDate, SalesOutputInterface $formatter)
    {
        $sales = $this->report->between($startDate, $endDate);
        $formatter->output($sales);
    }
}

// example usage.
$report = new SalsReporter(new SalesDbRepository());
$startDate = Carbon\Carbon::subDays(10);
$endDate = Carbon\Carbon::now();
$formatter = new HtmlOutput();
$report->between($startDate, $endDate, $formatter);
