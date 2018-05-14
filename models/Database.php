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
            echo 'Connection failed: '.$errorMsg;
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
            echo 'Connection failed: '.$errorMsg;
        }
    }

    public static function select($database, $columns, $tables, $where = '', $jsonParams = '', $groupBy = '', $having = '', $order = '', $limit = 0, $offset = 0, $columnData = false)
    {
        if (!empty($database)) {
            if (!empty($tables)) {
                if (!empty($columns)) {
                    $sql = "SELECT $columns FROM $tables";
                } else {
                    $sql = "SELECT * FROM $tables";
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

                if (!empty($limit) && intval($limit) > 0) {
                    $sql .= " LIMIT $limit";
                    if (!empty($offset) && intval($offset) > 0) {
                        $sql .= " OFFSET $offset";
                    }
                }

                return self::rawSelectQuery($database, $sql, $jsonParams, $columnData);
            }

            return 'Please specify tables\' name for the SELECT query';
        }

        return 'Please specify which database to be used';
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
                if (is_array($jsonParams)) {
                    $tempBindParams = json_encode($jsonParams);
                } else {
                    $tempBindParams = $jsonParams;
                }
                $tempBindParams = replaceSpecialChars($tempBindParams);
                $arrayBindParams = json_decode($tempBindParams, true);

                foreach ($arrayBindParams as $key => &$val) {
                    $stmt->bindParam(':'.$key, $val);
                }
            }

            $stmt->execute();
            if ($columnData) {
                $array = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return json_encode($array);
        }

        return 'Please provide the query to be run';
    }

    public static function addRows($database, $tablename, $jsonNewRows, $ignoreIfDuplicate = false)
    {
        $errorLog = '';
        if (empty($database)) {
            return 'Please provide the name of the database';
        }
        if (empty($tablename)) {
            return 'Please provide the name of the table';
        }
        if (empty($jsonNewRows)) {
            return 'No data to be inputted into the table';
        }

        require_once __DIR__.'/../data/string.php';
        if (is_array($jsonNewRows)) {
            $tempNewRow = json_encode($jsonNewRows);
        } else {
            $tempNewRow = $jsonNewRows;
        }
        $tempNewRow = replaceSpecialChars($tempNewRow);
        $arrayNewRows = json_decode($tempNewRow, true);
        if (is_array($arrayNewRows)) {
            $arrayUsed = [];
            if (is_null($arrayNewRows[0])) { // only 1 row of data
                $arrayUsed[] = $arrayNewRows;
            } else {
                $arrayUsed = $arrayNewRows;
            }
            $connection = self::getConnectionWrite($database);
            if ($ignoreIfDuplicate) {
                $sqlQuery = "INSERT IGNORE INTO $tablename";
            } else {
                $sqlQuery = "INSERT INTO $tablename";
            }
            $dataColumns = array_keys($arrayUsed[0]);
            $sqlQuery .= ' (`'.implode($dataColumns, '`, `').'`) ';
            $sqlQuery .= ' VALUES (:'.implode($dataColumns, ', :').')';

            $stmt = $connection->prepare($sqlQuery);

            foreach ($arrayUsed as $data) {
                foreach ($data as $key => &$val) {
                    $stmt->bindParam(':'.$key, $val);
                }
                if ($stmt->execute()) {
                    //return 'Insert success. Id='.$connection->lastInsertId();
                } else {
                    $errorLog .= 'insert failed: '.$connection->errorInfo().PHP_EOL;
                }
            }
            if (empty($errorLog)) {
                return 'Insert success.';
            }

            return $errorLog;
        }

        return 'Please provide the new data in json format';
    }

    public static function update($database, $tablename, $jsonNewRow, $whereQuery = '', $jsonWhereParams = '')
    {
        if (!empty($tablename)) {
            if (!empty($jsonNewRow)) {
                require_once __DIR__.'/../data/string.php';
                if (is_array($jsonNewRow)) {
                    $tempNewRow = json_encode($jsonNewRow);
                } else {
                    $tempNewRow = $jsonNewRow;
                }
                $tempNewRow = replaceSpecialChars($tempNewRow);
                $arrayNewRow = json_decode($tempNewRow, true);
                if (is_array($arrayNewRow)) {
                    $sqlQuery = "UPDATE $tablename SET ";

                    foreach ($arrayNewRow as $key => &$val) {
                        if (empty($val)) {
                            $sqlQuery .= "$key = DEFAULT,";
                        } else {
                            $sqlQuery .= "$key=:".$key.'_update,';
                        }
                    }

                    $sqlQuery = rtrim($sqlQuery, ',');
                    if (!empty($whereQuery)) {
                        $whereQuery = replaceSpecialChars($whereQuery);
                        $sqlQuery .= " WHERE $whereQuery";
                    }

                    $connection = self::getConnectionWrite($database);
                    $stmt = $connection->prepare($sqlQuery);
                    foreach ($arrayNewRow as $key => &$val) {
                        if (!empty($val)) {
                            $stmt->bindParam(':'.$key.'_update', $val);
                        }
                    }
                    if (!empty($jsonWhereParams)) {
                        if (is_array($jsonWhereParams)) {
                            $tempWhereParams = json_encode($jsonWhereParams);
                        } else {
                            $tempWhereParams = $jsonWhereParams;
                        }
                        $tempWhereParams = replaceSpecialChars($tempWhereParams);
                        foreach (json_decode($tempWhereParams, true) as $key => &$val) {
                            $stmt->bindParam(':'.$key, $val);
                        }
                    }

                    if ($stmt->execute()) {
                        $rowsUpdated = $stmt->rowCount();
                        if ($rowsUpdated == 1) {
                            return "$rowsUpdated record was successfully updated";
                        } elseif ($rowsUpdated > 1) {
                            return "$rowsUpdated records were successfully updated";
                        }

                        return 'no record updated';
                    }

                    return 'update failed: '.$connection->errorInfo();
                }

                return 'Data isn\'t in Array format';
            }

            return 'No data to be inputted into the table';
        }

        return 'Please provide the name of the database table';
    }

    public static function delete($database, $tablename, $whereQuery = '', $jsonWhereParams = '', $ignoreIfDuplicate = false)
    {
        if (!empty($tablename)) {
            if (!empty($whereQuery)) {
                if ($ignoreIfDuplicate) {
                    $sql = "DELETE IGNORE FROM $tablename WHERE $whereQuery";
                } else {
                    $sql = "DELETE FROM $tablename WHERE $whereQuery";
                }
                $connection = self::getConnectionWrite($database);
                $stmt = $connection->prepare($sql);
                if (!empty($jsonWhereParams)) {
                    require_once __DIR__.'/../data/string.php';
                    if (is_array($jsonWhereParams)) {
                        $tempParams = json_encode($jsonWhereParams);
                    } else {
                        $tempParams = $jsonWhereParams;
                    }
                    $tempParams = replaceSpecialChars($tempParams);
                    $arrayParams = json_decode($tempParams, true);
                    if (isset($arrayParams[0])) {
                        $rowsDeleted = 0;
                        foreach ($arrayParams as &$baris) {
                            foreach ($baris as $key => &$val) {
                                $stmt->bindParam(':'.$key, $val);
                            }
                            $stmt->execute();
                            $rowsDeleted += $stmt->rowCount();
                        }
                    } else {
                        foreach ($arrayParams as $key => &$val) {
                            $stmt->bindParam(':'.$key, $val);
                        }
                        $stmt->execute();
                        $rowsDeleted = $stmt->rowCount();
                    }
                } else {
                    $stmt->execute();
                    $rowsDeleted = $stmt->rowCount();
                }

                if ($rowsDeleted > 0) {
                    return "$rowsDeleted rows successfully deleted.";
                }

                return 'delete failed: '.$connection->errorInfo();
            }

            return 'Please provide the WHERE clause for this query';
        }

        return 'Please provide the name of the database table';
    }

    public static function CalculateOffset($currentpage, $currentrow, $limit)
    {
        if (!empty($limit) && intval($limit) > 0) {
            if (!empty($currentrow)) {
                return intval($currentrow);
            } else {
                if (empty($currentpage) || intval($currentpage) == 0) {
                    $currentpage = 1;
                }

                return (intval($currentpage) - 1) * intval($limit);
            }
        }

        return 0;
    }
}
