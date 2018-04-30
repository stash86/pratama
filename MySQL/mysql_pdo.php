<?php

function RunInsertQuery($connection, $tablename, $jsonNewRow, $ignoreIfDuplicate = false)
{
    require_once __DIR__.'/string.php';
    if (!empty($tablename)) {
        if (!empty($jsonNewRow)) {
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
                } else {
                    return 'insert failed: '.$connection->errorInfo();
                }
            } else {
                return 'Data isn\t in Array format';
            }
        } else {
            return 'No data to be inputted into the table';
        }
    } else {
        return 'Please provide the name of the database table';
    }
}

function RunMultipleInsertQuery($connection, $tablename, $arrayColumnNames, $jsonNewRows, $ignoreIfDuplicate = false)
{
    require_once __DIR__.'/string.php';
    $errorLog = '';
    if (!empty($tablename)) {
        if (!empty($jsonNewRows)) {
            if (!empty($arrayColumnNames) && is_array($arrayColumnNames)) {
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
                } else {
                    return 'Data isn\t in Array format';
                }
            } else {
                return 'Please provide the name of the columns in Array format';
            }
        } else {
            return 'No data to be inputted into the table';
        }
    } else {
        return 'Please provide the name of the database table';
    }

    if (empty($errorLog)) {
        return 'Insert success.';
    } else {
        return $errorLog;
    }
}
