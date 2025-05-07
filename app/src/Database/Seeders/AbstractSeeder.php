<?php
namespace App\Database\Seeders;

use App\Database\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractSeeder
{
    protected \PDO $pdo;
    protected static array $instances = [];
    private ConsoleOutput $cout;
    private SymfonyStyle $io;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
        $this->cout = new ConsoleOutput();
        $this->io = new SymfonyStyle(new ArrayInput([]), $this->cout);
    }

    public abstract function run();

    public function seed() 
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }
            $this->run();
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            $this->io->success("Seeding completed");
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->io->error("Seeding failed: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function truncate(string $tableName): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->pdo->exec("TRUNCATE TABLE $tableName");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public static function instance(): self
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }
        return self::$instances[$class];
    }
}
