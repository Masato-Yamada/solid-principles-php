<?php

class SalesReporter {
    public function getSalesBetween($startDate, $endDate)
    {
        $sales = $this->queryDbForSalesBetween($startDate, $endDate);
        return $this->format($sales);
    }
    protected function queryDbForSalesBetween($startDate, $endDate)
    {
        return DB::table('sales')->whereBetween('create_at', [$startDate, $endDate])->sum('amount');
    }
    protected function format($sales)
    {
        echo "<h1>your sales: ".$sales."</h1>" ;
    }
}

//usage
$report = new SalesReporter;
$begin = Carbon\Carbon::now()->subDays(10);
$end = Carbon\Carbon::now();
$report->between($begin, $end);

?>
