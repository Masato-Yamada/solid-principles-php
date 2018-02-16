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

#### 演習問題

以下の変更を、SRPに準じたコード、SRPに準じていないコードの両方に実装してください。
- 出力フォーマットを「&lt;h3&gt;売り上げ金額：○○円&lt;/h3&gt;」としてください。
- ORマッパーに脆弱性があったため、生のSQLを書いて、データを取得するように変更してください。  
生のクエリーは
```
DB::query($sql)
```
で実行する事ができます。
- 期が変わってしまうので、4/1をまたいで売り上げ金額を取得できないようにしてください。  （3/25〜4/15みたいな指定ができないようにする）

SRPを理解するための仮想コードなので、細かい部分はあまり気にしないようにお願いします。。。

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

#### 演習問題

以下の変更を、OCPに準じたコード、OCPに準じていないコードの両方に実装してください。
- 戦車(Tank)を実装してください。タンク容量は100リットルです。


#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/OpenClose/uml/uml.png)

#### **リスコフの置換原則（LSP: The Liskov Substitution Principle）**
派生型はその基本型と置換可能でなければならない

以下の例は、典型的なリスコフの置換原則に違反している例です。
DriverクラスのgoメソッドにVehicle型のクラスを与えていますが、クラスの型によってgoメソッドの挙動が変わってしまっています。これはリスコフの置換原則に違反しています。

LSPに準じていないコード
```php
class Vehicle
{
    public function startEngine()
    {
        $this->checkKey();
    }
    public function accelerate()
    {
        //default acceleration procedure
    }
    public function checkKey()
    {
        // check key
    }
}
class Car extends Vehicle
{
    public function checkTank(){
        //check gas procedure
    }
}

class ElectricCar extends Vehicle
{
    public function increaseVoltage(){
        // increase voltage procedure
    }
}
class Driver
{
    function go(Vehicle $vehicle) {
        if( $vehicle instanceof Car ) {
            $vehicle->checkTank();
        }

        $vehicle->startEngine();

        if( $vehicle instanceof ElectricCar ) {
            $this->increaseVoltage();
        }

        $vehicle->accelerate();
    }
}
```

上記をリファクタリングしたのが、以下の例です。
LSPに準じたコード
```php
class Vehicle
{
    public function startEngine()
    {
        $this->checkKey();
    }
    public function accelerate()
    {
        //default acceleration procedure
    }
    public function checkKey()
    {
        // check key
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
これによって、クラスの型によらず、Driverクラスのgoメソッドの振る舞いは何も変化しなくなりました。
この原則は、オープンクローズドの原則の拡張と考えられます。In conclusion this principle is just an extension of the Open Close Principle and it means that we must make sure that new derived classes are extending the base classes without changing their behavior.

#### 演習問題
LSPに準じたコード、LSPに準じていないコードの両方に、新しい車種、天然ガス車（GasCar）を追加してみましょう。
天然ガス車は、エンジンをかける前にガス漏れがないか確認する必要があります。（checkLeakGas()）
また、天然ガス車はスマートキーなので、checkKey()は不要になります。CarとElectricCarにはcheckKey()は必要です。
Vehicleクラスから処理をうまく除いてみてください。

#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/LiskovSubstitution/uml/uml.png)

#### **インタフェース分離の原則（ISP: The Interface Segregation Principle）**
上位のモジュールは下位のモジュールに依存してはならない。どちらのモジュールも「抽象」に依存すべきである。
「抽象」は実装の詳細に依存してはならない。実装の詳細が「抽象」に依存すべきである。

ISPに準じていないコード
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
Robotはeatメソッドは必要ないので、空の実装になっています。しかし、このように使用しないメソッドに依存してはいけません。

ISPに準じたコード
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
Robotクラスは必要なインターフェースのみ実装するようになりました。これで、IFeedableに何か変更があっても、Robotクラスは
何も影響されないようになりました！

#### 演習問題
ISPに準じたコード、ISPに準じていないコードの両方に、IFeedableのeatメソッドシグネチャを変更してください。
eatメソッドの引数としてfoodKindが設定できるようにしてください。

#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/InterfaceSegregation/uml/uml.png)

#### **依存関係逆転の原則（DIP: The Dependency Inversion Principle）**
上位のモジュールは下位のモジュールに依存してはならない。どちらのモジュールも「抽象」に依存すべきである。
「抽象」は実装の詳細に依存してはならない。実装の詳細が「抽象」に依存すべきである。

手続き型のプログラミングでは、上位のモジュールが下位のモジュールに依存したりするが、本来は上位のモジュールが
下位のモジュールに影響を与えるべきで、下位のモジュールが変更されたからといって、上位のモジュールに影響があってはいけない。
手続き型のプログラミング時代の依存関係を「逆転させる」のが、この原則です。

DIPに準じていないコード
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

DIPに準じたコード
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

#### 演習問題
DIPに準じたコード、DIPに準じていないコードの両方に以下の変更を加えてください。
```
//example usage
$manager = new Manager();
$manager->setWorker(new Worker());
$manager->manage();
```
上記のコードがあった時に、manageからSuperWorkerのworkが呼ばれるように変更してください。

#### UML diagram:
![alt tag](https://github.com/Masato-Yamada/solid-principles-php/blob/master/DependencyInversion/uml/uml.png)
