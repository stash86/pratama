<?php

class Database
{
    public static function getConnectionRead($database)
    {
        static $pdo_instance_read;

        try {
            if (!isset($pdo_instance_read)) {
                global $CONFIG;
                $databaseName = 'db_database_'.$database;
                $pdo_instance_read = new PDO('mysql:hostname='.$CONFIG['db_hostname'].';dbname='.$CONFIG[$databaseName].';charset=utf8', $CONFIG['db_username_read'], $CONFIG['db_password_read']);
                $pdo_instance_read->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            return $pdo_instance_read;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            echo htmlentities("Connection failed: $errorMsg");
        }
    }

    public static function getConnectionWrite($database)
    {
        static $pdo_instance_write;

        try {
            if (!isset($pdo_instance_write)) {
                global $CONFIG;
                $databaseName = 'db_database_'.$database;
                $pdo_instance_write = new PDO('mysql:hostname='.$CONFIG['db_hostname'].';dbname='.$CONFIG[$databaseName].';charset=utf8', $CONFIG['db_username_write'], $CONFIG['db_password_write']);
                $pdo_instance_write->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            return $pdo_instance_write;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            echo htmlentities("Connection failed: $errorMsg");
        }
    }

    public static function select($database, $columns, $tables, $where = '', $jsonParams = '', $groupBy = '', $having = '', $order = '', $limit = 0, $offset = 0, $columnData = false)
    {
        if (!empty($database) && !empty($tables)) {
            $sql = "SELECT * FROM $tables";
            if (!empty($columns)) {
                $sql = "SELECT $columns FROM $tables";
            }

            if (!empty($where)) {
                $sql .= " WHERE $where";
            }

            if (!empty($groupBy)) {
                $sql .= " GROUP BY $groupBy";
            }

            if (!empty($having)) {
                $sql .= " HAVING $having";
            }

            if (!empty($order)) {
                $sql .= " ORDER BY $order";
            }

            if (intval($limit) > 0) {
                $sql .= " LIMIT $limit";
                if (intval($offset) > 0) {
                    $sql .= " OFFSET $offset";
                }
            }

            return self::rawSelectQuery($database, $sql, $jsonParams, $columnData);
        }

        return 'Please specify which database and tables to be used';
    }

    public static function selectSingleResult($database, $column, $tables, $where = '', $jsonParams = '', $groupBy = '', $having = '', $order = '')
    {
        return json_decode(self::select($database, $column, $tables, $where, $jsonParams, $groupBy, $having, $order, 1, 0, true), true)[0];
    }

    public static function rawSelectQuery($database, $sqlQuery, $jsonParams = '', $columnData = false)
    {
        if (!empty($sqlQuery)) {
            require_once __DIR__.'/../data/string.php';
            $sqlQuery = replaceSpecialChars($sqlQuery);
            $connection = self::getConnectionRead($database);
            $stmt = $connection->prepare($sqlQuery);
            if (!empty($jsonParams)) {
                $jsonParams = json_encode($jsonParams);
                $jsonParams = replaceSpecialChars($jsonParams);
                $jsonParams = json_decode($jsonParams, true);

                foreach ($jsonParams as $key => &$val) {
                    $stmt->bindParam(':'.$key, $val);
                }
            }

            $stmt->execute();
            if ($columnData) {
                return json_encode($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
            }

            return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return 'Please provide the query to be run';
    }

    public static function addRows($database, $tablename, $jsonNewRows, $ignoreIfDuplicate = false)
    {
        $errorLog = '';
        if (empty($database) || empty($tablename) || empty($jsonNewRows)) {
            return 'One or morw required inputs are empty. Please check!!';
        }

        require_once __DIR__.'/../data/string.php';
        $jsonNewRows = json_encode($jsonNewRows);
        $jsonNewRows = replaceSpecialChars($jsonNewRows);
        $jsonNewRows = json_decode($jsonNewRows, true);
        if (is_null($jsonNewRows[0])) { // not in json format
            $jsonNewRows = [$jsonNewRows];
        }

        $connection = self::getConnectionWrite($database);

        $sqlQuery = "INSERT INTO $tablename";
        if ($ignoreIfDuplicate) {
            $sqlQuery = "INSERT IGNORE INTO $tablename";
        }

        $dataColumns = array_keys($jsonNewRows[0]);
        $sqlQuery .= ' (`'.implode($dataColumns, '`, `').'`) ';
        $sqlQuery .= ' VALUES (:'.implode($dataColumns, ', :').')';

        $stmt = $connection->prepare($sqlQuery);

        foreach ($jsonNewRows as $data) {
            foreach ($data as $key => &$val) {
                $stmt->bindParam(':'.$key, $val);
            }
            if (!($stmt->execute())) {
                $errorLog .= 'insert failed: '.$connection->errorInfo().PHP_EOL;
            }
        }
        if (empty($errorLog)) {
            return 'Insert success.';
        }

        return $errorLog;
    }

    public static function update($database, $tablename, $jsonNewRow, $whereQuery = '', $jsonWhereParams = '')
    {
        if (empty($database) || empty($tablename) || empty($jsonNewRow) || empty($whereQuery)) {
            return 'Please provide all the required data';
        }

        require_once __DIR__.'/../data/string.php';

        $jsonNewRow = json_encode($jsonNewRow);
        $jsonNewRow = replaceSpecialChars($jsonNewRow);
        $jsonNewRow = json_decode($jsonNewRow, true);

        $sqlQuery = "UPDATE $tablename SET ";

        foreach ($jsonNewRow as $key => &$val) {
            if (empty($val)) {
                $sqlQuery .= "$key = DEFAULT,";
            } else {
                $sqlQuery .= "$key=:".$key.'_update,';
            }
        }

        $sqlQuery = rtrim($sqlQuery, ',');
        $whereQuery = replaceSpecialChars($whereQuery);
        $sqlQuery .= " WHERE $whereQuery";

        $connection = self::getConnectionWrite($database);
        $stmt = $connection->prepare($sqlQuery);
        foreach ($jsonNewRow as $key => &$val) {
            if (!empty($val)) {
                $stmt->bindParam(':'.$key.'_update', $val);
            }
        }
        if (!empty($jsonWhereParams)) {
            $jsonWhereParams = json_encode($jsonWhereParams);
            $jsonWhereParams = replaceSpecialChars($jsonWhereParams);
            foreach (json_decode($jsonWhereParams, true) as $key => &$val) {
                $stmt->bindParam(':'.$key, $val);
            }
        }

        if ($stmt->execute()) {
            $rowsUpdated = $stmt->rowCount();

            return "$rowsUpdated record(s) successfully updated";
        }

        return 'update failed: '.$connection->errorInfo();
    }

    public static function delete($database, $tablename, $whereQuery = '', $jsonWhereParams = '', $ignoreError = false)
    {
        if (empty($database) || empty($tablename) || empty($whereQuery) || empty($jsonWhereParams)) {
            return 'Please provide all the required inputs';
        }

        $sql = "DELETE FROM $tablename WHERE $whereQuery";
        if ($ignoreError) {
            $sql = "DELETE IGNORE FROM $tablename WHERE $whereQuery";
        }
        $connection = self::getConnectionWrite($database);
        $stmt = $connection->prepare($sql);
        require_once __DIR__.'/../data/string.php';
        $jsonWhereParams = json_encode($jsonWhereParams);
        $jsonWhereParams = replaceSpecialChars($jsonWhereParams);
        $jsonWhereParams = json_decode($jsonWhereParams, true);
        if (!isset($jsonWhereParams[0])) {
            $jsonWhereParams = [$jsonWhereParams];
        }

        $rowsDeleted = 0;
        foreach ($jsonWhereParams as &$baris) {
            foreach ($baris as $key => &$val) {
                $stmt->bindParam(':'.$key, $val);
            }
            $stmt->execute();
            $rowsDeleted += $stmt->rowCount();
        }

        return "$rowsDeleted rows successfully deleted. Error(s) happened was ".$connection->errorInfo();
    }

    public static function CalculateOffset($currentpage, $currentrow, $limit)
    {
        if (!empty($limit) && intval($limit) > 0) {
            if (!empty($currentrow)) {
                return intval($currentrow);
            }

            $currentpage = max(1, intval($currentpage));

            return ($currentpage - 1) * intval($limit);
        }

        return 0;
    }
}
