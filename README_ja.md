## コードを書く時に従うべき5つのアジャイル原則

これらの原則を組み合わせてコードを書くと、拡張性、メンテナンス性に優れたコードを書く事ができます。さらにリファクタリングしやすく、腐敗を防ぐことで、アジャイル開発を容易にします。

#### 例:

#### **単一責任の原則（SRP: The Single Responsibility Principle）**
クラスを変更の理由は1つ以上存在してはならない

機能をカプセル化したモデムの実装クラスを見てみましょう。

SRPに準じていないコード
```php
<?
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
```

SalesReporterクラスには、3つの「変更の理由」があります。  
- 永続性のあるシステムからデータを取り出す（技術的関心事）
- 出力フォーマットの指定（プレゼンテーション）
- 特定の期間の売り上げ金額を取得（ビジネスロジック）

ビジネスロジックとプレゼンテーションをごっちゃにするのはよくありません。SRP違反となります。
また、技術的関心事とドメインの関心事が一緒になっているのもよくありません。（技術的関心事とドメインの関心事の分離について詳しく知りたい方はDDDを勉強してください）

次のコードを見てみましょう。

SRPに準じたコード
```php
<?
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
```
SalesReporterから3つの役割を分離し、以下のようなクラス分けをしました。
- 永続性のあるシステムからデータを取り出す（技術的関心事）-> SalesRepositoryInterface, SalesRepository
- 出力フォーマットの指定（プレゼンテーション）-> SalesOutputInterface, HtmlOutput
- 特定の期間の売り上げ金額を取得（ビジネスロジック） -> SalesReporter

分離する事で、SRPを守っています。これにより、柔軟性が増しました。

演習問題：以下の変更を、SRPに準じたコード、SRPに準じていないコードの両方に実装してください。
- 出力フォーマットを「<h3>売り上げ金額：○○円</h3>」としてください。
- ORマッパーに脆弱性があったため、生のSQLを書いて、データを取得するように変更してください。
- 期が変わってしまうので、4/1をまたいで売り上げ金額を取得できないようにしてください。  （3/25〜4/15みたいな指定ができないようにする）

#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/SingleResponsibility/uml/uml.png)

#### **オープンクローズドの原則（OCP: The Open Closed Principle）**

拡張に対して開いていて、修正に対して閉じていなければならない

抽象的で非常に学術的に聞こえるかもしれないが、要は、仕様変更の度にコードを修正しなくても良いようにコードと格闘しなければいけない、ということです。

ガソリンスタンドで、車にガソリンを入れる手順の例を以下に示します。以下のコードは正常に動作しますが、他の車種にガソリンを入れようとした時に問題が発生します。
それは、車種が増える度に、いちいち"putGasInVehicle()"メソッドを修正しなければいけません。これは、OCPに反しています。

OCPに準じていないコード
```php
<?
class GasStation
{
    public function putGasInVehicle(Vehicle $vehicle)
    {
        if ($vehicle->getType() == 1)
            $this->putGasInCar($vehicle);
        elseif ($vehicle->getType() == 2)
            $this->putGasInMotorcycle($vehicle);
    }
    public function putGasInCar(Car $car)
    {
        $car->setTank(50);
    }
    public function putGasInMotorcycle(Motorcycle $motorcycle)
    {
        $motorcycle->setTank(20);
    }
}
class Vehicle
{
    protected $type;
    protected $tank;
    public function getType()
    {
        return $this->type;
    }
    public function setTank($tank)
    {
        $this->tank = $tank;
    }
}
class Car extends Vehicle
{
    protected $type = 1;
}
class Motorcycle extends Vehicle
{
    protected $type = 2;
}
```


OCPに準じたコード
```php
<?
class GasStation
{
    public function putGasInVehicle(Vehicle $vehicle)
    {
       $vehicle->putGasIn();
    }
}
abstract class Vehicle
{
    protected $tank;
    public function setTank($tank)
    {
        $this->tank = $tank;
    }
    abstract public function putGasIn();
}
class Car extends Vehicle
{
    public function putGasIn()
    {
        $this->setTank(50);
    }
}
class Motorcycle extends Vehicle
{
    public function putGasIn()
    {
        $this->setTank(20);
    }
}
```

演習問題：以下の変更を、OCPに準じたコード、OCPに準じていないコードの両方に実装してください。
- 戦車(Tank)を実装してください。タンク容量は100リットルです。


#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/OpenClose/uml/uml.png)

#### **LSP	The Liskov Substitution Principle**
Derived classes must be substitutable for their base classes.

Below is the classic example for which the Likov Substitution Principle is violated. Let's assume that the Rectangle object is used somewhere in the application. We extend the application and add the Square class. The square class is returned by a factory pattern, based on some conditions and we don't know the exact what type of object will be returned.

```php
class Rectangle
{
    /** @var  integer */
    protected $width;
    /** @var  integer */
    protected $height;
    /**
     * @param $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
    /**
     * @param $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->height * $this->width;
    }
}
class Square extends Rectangle
{
    /**
     * @param $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
        $this->height = $width;
    }
    /**
     * @param $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
        $this->width = $height;
    }
}
```
Valid example
```php
class Vehicle
{
    public function startEngine()
    {
        // default engine start procedure
    }
    public function accelerate()
    {
        //default acceleration procedure
    }
}
class Car extends Vehicle
{
    public function startEngine()
    {
        $this->checkTank();
        parent::startEngine();
    }
    private function checkTank()
    {
        //check gas procedure
    }
}
class ElectricCar extends Vehicle
{
    public function accelerate()
    {
        $this->increaseVoltage();
    }
    private function increaseVoltage()
    {
        // increase voltage procedure
    }
}
class Driver
{
    function go(Vehicle $vehicle) {
        $vehicle->startEngine();
        $vehicle->accelerate();
    }
}
```
In conclusion this principle is just an extension of the Open Close Principle and it means that we must make sure that new derived classes are extending the base classes without changing their behavior.

####UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/LiskovSubstitution/uml/uml.png)

####**ISP	The Interface Segregation Principle**
Make fine grained interfaces that are client specific.

Violated example
```php
interface IWorker
{
    public function work();
    public function eat();
}
class Worker implements IWorker
{
    public function work()
    {
        // working
    }
    public function eat()
    {
        // eating in launch break
    }
}
class Robot implements IWorker
{
    public function work()
    {
        // working 24 hours per day
    }
    public function eat()
    {
        // doesn't need this method
    }
}
```
Valid example:
```php
interface IWorkable
{
    public function work();
}
interface IFeedable
{
    public function eat();
}
class Worker implements IWorkable, IFeedable
{
    public function work()
    {
        // ....working
    }
    public function eat()
    {
        //.... eating in launch break
    }
}
class Robot implements IWorkable
{
    public function work()
    {
        // ....working
    }
}
class SuperWorker implements IWorkable, IFeedable
{
    public function work()
    {
        //.... working much more
    }
	public function eat()
    {
        //.... eating in launch break
    }
}
```

####UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/InterfaceSegregation/uml/uml.png)

####**DIP	The Dependency Inversion Principle**
Depend on abstractions, not on concretions.

Violated example
```php
class Worker
{
    public function work()
    {
        // ....working
    }
}
class SuperWorker
{
    public function work()
    {
        //.... working much more
    }
}
class Manager
{
    /** @var Worker */
    private $worker;
    /**
     * @param Worker $worker
     */
    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;
    }
    public function manage()
    {
        $this->worker->work();
    }
}
```
Valid example:
```php
interface IWorker
{
    public function work();
}
class Worker implements IWorker
{
    public function work()
    {
        // ....working
    }
}
class SuperWorker  implements IWorker
{
    public function work()
    {
        //.... working much more
    }
}
class Manager
{
    /** @var IWorker */
    private $worker;
    /**
     * @param IWorker $worker
     */
    public function setWorker(IWorker $worker)
    {
        $this->worker = $worker;
    }
    public function manage()
    {
        $this->worker->work();
	}
}
```

####UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/DependencyInversion/uml/uml.png)
