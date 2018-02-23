<?php

interface SalesOutputInterface {
    public function output();
}
class HtmlOutput implements SalesOutputInterface {
    public function output($sales)
    {
        echo "<h3>売り上げ金額：{$sales}円</h3>";
    }
}

interface SalesRepositoryInterface {
    public function between();
}
class SalesDbRepository implements SalesRepositoryInterface{
    public function between($startDate, $endDate)
    {
        return DB::query("select sum(amount) from sales where between create_at ".$startDate ." and ". $endDate);
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
        if( $startDate <= "3/31" and $endDate >= "4/1" ) {
             $sales = "";
        }
        else {
            $sales = $this->report->between($startDate, $endDate);
        }
        $formatter->output($sales);
    }
}

// example usage.
$report = new SalsReporter(new SalesDbRepository());
$startDate = Carbon\Carbon::subDays(10);
$endDate = Carbon\Carbon::now();
$formatter = new HtmlOutput();
$report->between($startDate, $endDate, $formatter);
