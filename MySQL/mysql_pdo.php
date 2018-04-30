<?php

function RunInsertQuery($connection, $tablename, $jsonNewRow, $ignoreIfDuplicate = false)
{
    if (!empty($tablename)) {
        if (!empty($jsonNewRow)) {
            require_once __DIR__.'/string.php';
            $jsonNewRow = replaceSpecialChars($jsonNewRow);
            $arrayNewRow = json_decode($jsonNewRow, true);
            if (is_array($arrayNewRow)) {
                if ($ignoreIfDuplicate) {
                    $sqlQuery = "INSERT IGNORE INTO $tablename";
                } else {
                    $sqlQuery = "INSERT INTO $tablename";
                }

                $dataColumns = array_keys($arrayNewRow);

                if (isset($dataColumns[0])) {
                    $sqlQuery .= ' (`'.implode($dataColumns, '`, `').'`) ';
                }

                $sqlQuery .= ' VALUES (';
                foreach ($arrayNewRow as $key => &$val) {
                    if (empty($val)) {
                        $sqlQuery .= 'DEFAULT,';
                    } else {
                        $sqlQuery .= ':'.$key.'_new,';
                    }
                }
                $sqlQuery = rtrim($sqlQuery, ',');
                $sqlQuery .= ')';

                $stmt = $connection->prepare($sqlQuery);
                foreach ($arrayNewRow as $key => &$val) {
                    if (!empty($val)) {
                        $stmt->bindParam(':'.$key.'_new', $val);
                    }
                }

                if ($stmt->execute()) {
                    return 'Insert success. Id='.$connection->lastInsertId();
                }

                return 'insert failed: '.$connection->errorInfo();
            }

            return 'Data isn\t in Array format';
        }

        return 'No data to be inputted into the table';
    }

    return 'Please provide the name of the database table';
}

function RunMultipleInsertQuery($connection, $tablename, $arrayColumnNames, $jsonNewRows, $ignoreIfDuplicate = false)
{
    $errorLog = '';
    if (!empty($tablename)) {
        if (!empty($jsonNewRows)) {
            if (!empty($arrayColumnNames) && is_array($arrayColumnNames)) {
                require_once __DIR__.'/string.php';
                $jsonNewRows = replaceSpecialChars($jsonNewRows);
                $arrayNewRows = json_decode($jsonNewRows, true);
                if (is_array($arrayNewRows)) {
                    if ($ignoreIfDuplicate) {
                        $sqlQuery = "INSERT IGNORE INTO $tablename";
                    } else {
                        $sqlQuery = "INSERT INTO $tablename";
                    }

                    $sqlQuery .= ' (`'.implode($arrayColumnNames, '`, `').'`) ';

                    $dataColumns = array_keys($arrayNewRows[0]);
                    $sqlQuery .= ' VALUES (:'.implode($dataColumns, ', :').')';

                    $stmt = $connection->prepare($sqlQuery);

                    foreach ($arrayNewRows as $id => &$data) {
                        foreach ($data as $key => &$val) {
                            $stmt->bindParam(':'.$key, $val);
                        }
                        if ($stmt->execute()) {
                            //return 'Insert success. Id='.$connection->lastInsertId();
                        } else {
                            $errorLog .= 'insert failed: '.$connection->errorInfo().PHP_EOL;
                        }
                    }
                }

                return 'Data isn\t in Array format';
            }

            return 'Please provide the name of the columns in Array format';
        }

        return 'No data to be inputted into the table';
    }

    return 'Please provide the name of the database table';

    if (empty($errorLog)) {
        return 'Insert success.';
    }

    return $errorLog;
}

function RunRawSelectQuery($connection, $sqlQuery)
{
    $array = [];
    if (!empty($sqlQuery)) {
        $stmt = $connection->prepare($sqlQuery);
        $stmt->execute();
        $array = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return json_encode($array);
}

function RunUpdateQuery($connection, $tablename, $jsonNewRow, $whereQuery = '', $jsonWhereParams = '')
{
    if (!empty($tablename)) {
        if (!empty($jsonNewRow)) {
            require_once __DIR__.'/string.php';
            $jsonNewRow = replaceSpecialChars($jsonNewRow);
            $arrayNewRow = json_decode($jsonNewRow, true);
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

                $stmt = $connection->prepare($sqlQuery);
                foreach ($arrayNewRow as $key => &$val) {
                    if (!empty($val)) {
                        $stmt->bindParam(':'.$key.'_update', $val);
                    }
                }
                if (!empty($jsonWhereParams)) {
                    $jsonWhereParams = replaceSpecialChars($jsonWhereParams);
                    foreach (json_decode($jsonWhereParams, true) as $key => &$val) {
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

            return 'Data isn\t in Array format';
        }

        return 'No data to be inputted into the table';
    }

    return 'Please provide the name of the database table';
}

function RunDeleteQuery($connection, $tablename, $whereQuery = '', $bindParams = '', $ignoreIfDuplicate = false)
{
    if (!empty($tablename)) {
        if (!empty($whereQuery)) {
            if ($ignoreIfDuplicate) {
                $sql = "DELETE IGNORE FROM $tablename WHERE $whereQuery";
            } else {
                $sql = "DELETE FROM $tablename WHERE $whereQuery";
            }

            $stmt = $connection->prepare($sql);
            if (!empty($bindParams)) {
                foreach (json_decode($bindParams, true) as $key => &$val) {
                    $stmt->bindParam(':'.$key, $val);
                }
            }
            $stmt->execute();
            $rowsDeleted = $stmt->rowCount();

            if ($rowsDeleted > 0) {
                return "$rowsDeleted rows successfully deleted.";
            }

            return 'delete failed: '.$connection->errorInfo();
        }

        return 'Please provide the WHERE clause for this query';
    }

    return 'Please provide the name of the database table';
}
