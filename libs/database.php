<?php

class Database
{
    private $config;
    private $connection = null;


    public function __construct()
    {
        $this->config = require_once dirname(__FILE__) . '/../config.php';

        if ($this->connection == null)
        {
            $this->connect();
        }
    }

    /** Dodawanie rekordów do bazy */

    public function insert($data = [], $table)
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "INSERT INTO ".$table." (".implode(',', $keys).") VALUES ('".implode('\',\'', $values)."')";

        return $this->connection->query($sql);
    }


    /** Wykonywanie zapytania + zwracanie wyników */

    public function query($sql = null, $return = false)
    {

        if ($return === true)
        {
            dump($sql);
            die();
        }

        $result = $this->connection->query($sql);

        $tmp = [];

        if ($result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                $tmp[] = $row;
            }

            return $tmp;
        }

        return false;
    }

    /** Połączenie z bazą danych */

    public function connect()
    {
        $this->connection = new mysqli($this->config['hostname'], $this->config['username'], $this->config['password'], $this->config['database']);

        if ($this->connection->connect_error)
        {
            die('Connection failed: '. $this->connection->connect_error);
        }
    }

    /** Zamykanie połączenia z bazą danych */

    public function close()
    {
        $this->connection->close();
    }
}

?>
