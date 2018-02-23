<?php

class SalesReporter {
    public function getSalesBetween($startDate, $endDate)
    {
        if( $startDate <= "3/31" and $endDate >= "4/1" ) {
            return false;
        }
        $sales = $this->queryDbForSalesBetween($startDate, $endDate);
        return $this->format($sales);
    }
    protected function queryDbForSalesBetween($startDate, $endDate)
    {
        return DB::query("select sum(`amount`) from sales where create_at between ".$startDate." and ". $endDate);
    }
    protected function format($sales)
    {
        echo "<h3>売り上げ金額：".$sales."円</h3>" ;
    }
}

//usage
$report = new SalesReporter;
$begin = Carbon\Carbon::now()->subDays(10);
$end = Carbon\Carbon::now();
$report->between($begin, $end);

?>
