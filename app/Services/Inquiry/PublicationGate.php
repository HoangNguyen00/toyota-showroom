<?php

namespace App\Services\Inquiry;

use Closure;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\Facades\DB;
use LogicException;
use PDO;
use RuntimeException;

/** One database-scoped gate, always acquired BEFORE any revocation/publication transaction. */
class PublicationGate
{
    private static bool $entered = false;

    private static ?Closure $verifyOwner = null;

    public function assertHeld(): void
    {
        if (! self::$entered || ! self::$verifyOwner || ! (self::$verifyOwner)()) {
            throw new RuntimeException('Publication gate ownership lost; operation must not publish.');
        }
    }

    public function run(Closure $operation): mixed
    {
        if (self::$entered || DB::transactionLevel() !== 0) {
            throw new LogicException('Publication gate requires a non-reentrant, outermost operation.');
        }
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = null;
        $file = null;
        $name = 'toyota:publication:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 40);
        if ($driver === 'mysql') {
            // Dedicated non-persistent session: no Laravel automatic reconnect or expiring lease.
            $config = $connection->getConfig();
            $config['options'][PDO::ATTR_PERSISTENT] = false;
            $pdo = (new MySqlConnector)->connect($config);
            $statement = $pdo->prepare('SELECT GET_LOCK(?, 10)');
            $statement->execute([$name]);
            if ((int) $statement->fetchColumn() !== 1) {
                throw new RuntimeException('Publication gate timeout; retry the operation.');
            }
        } elseif ($driver === 'sqlite') {
            // SQLite is for tests only; this is not evidence of MySQL cross-process locking.
            $file = fopen(sys_get_temp_dir().'/toyota-publication-'.hash('sha256', base_path().$name).'.lock', 'c');
            if ($file === false) {
                throw new RuntimeException('Cannot open publication gate.');
            }
            $deadline = microtime(true) + 10;
            while (! flock($file, LOCK_EX | LOCK_NB)) {
                if (microtime(true) >= $deadline) {
                    fclose($file);
                    throw new RuntimeException('Publication gate timeout; retry the operation.');
                }
                usleep(10000);
            }
        } else {
            throw new LogicException('Publication gate supports the documented MySQL/SQLite environments only.');
        }
        self::$entered = true;
        self::$verifyOwner = $pdo ? function () use ($pdo, $name): bool {
            // Raw PDO intentionally cannot reconnect and silently replace the lock-owning session.
            $query = $pdo->prepare('SELECT IS_USED_LOCK(?) = CONNECTION_ID()');
            $query->execute([$name]);

            return (int) $query->fetchColumn() === 1;
        } : fn (): bool => is_resource($file);
        try {
            return $operation();
        } finally {
            self::$entered = false;
            self::$verifyOwner = null;
            if ($pdo) {
                // Dropping the dedicated session releases its advisory lock, including exceptional exits.
                unset($statement);
                $pdo = null;
            }
            if ($file) {
                flock($file, LOCK_UN);
                fclose($file);
            }
        }
    }
}
