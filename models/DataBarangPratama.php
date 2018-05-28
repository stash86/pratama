<?php
class DataBarangPratama
{
	private $rows = [];
    private $totalMatchedRows;

	public function __construct($nameQuery, $priceQuery, $limit, $offset, $order='NamaBarang ASC', $showHiddenRows=false, $exact=false)
    {
        $where = '';
        $jsonParams=[];
        $nameQuery = urldecode(htmlspecialchars_decode($nameQuery));
        if(!empty($nameQuery)) {
            if($exact) {
                $where = 'data_barang.NamaBarang = :nameToFind';
                $jsonParams['nameToFind'] = $nameQuery;
            } else {
                $nameQuery = str_replace("'", "\'", $nameQuery);
                $where = 'data_barang.NamaBarang LIKE \'%'.implode('%\' AND data_barang.NamaBarang LIKE \'%', explode(' ', $nameQuery)).'%\'';
            }
        }

        if(!empty($priceQuery) && intval($priceQuery) > 0) {
            if(!empty($where)) {
                $where .= ' AND ';
            }
            $where .= 'data_barang.Harga = :priceToFind';
            $jsonParams['priceToFind'] = intval($priceQuery);
        }

        if(!$showHiddenRows) {
            if(!empty($where)) {
                $where .= ' AND ';
            }
            $where .= 'data_barang.Hide = 0';
        }

        $this->rows = json_decode(Database::rawSelectQuery(
            'pratama'
            , Database::createSelectQuery(
                ''
                , 'data_barang'
                , $where
                , ''
                , ''
                , $order
                , $limit
                , $offset
            )
            , $jsonParams
        ),true);
        
        $this->totalMatchedRows = Database::selectSingleResult(
            'pratama'
            , 'COUNT(*)'
            , 'data_barang'
            , $where 
            , $jsonParams
        );
    }

    public function getRows() {
        return $this->rows;
    }

    public function totalRows() {
        return $this->totalMatchedRows;
    }

    private function setRowValue($rowIndex, $arrayNewValue) {
        foreach ($arrayNewValue as $key => &$value) {
            $this->rows[$rowIndex][$key] = $value;
        }
    }

    public function updateValue($name, $arrayNewValues) {
        $indexToUpdate = $this->findIndexOf($name);
        if ($indexToUpdate<0) {
            return;
        }
        Database::update('pratama', 'data_barang', $arrayNewValues, 'NamaBarang=:Nama', ['Nama'=>$name]);
        $this->setRowValue($indexToUpdate, $arrayNewValues);
    }

    public function updateQuantity($name='') {
        $whereQuery = 'NamaBarang=:Nama';
        foreach ($this->rows as $row) {
            if (!empty($name) && strcmp($name, $row['NamaBarang']) !== 0) {
                continue;
            }
            
            $bindParamWhere = ['Nama'=>$row['NamaBarang']];
            $buyingQty = Database::selectSingleResult(
                'pratama'
                , 'SUM(Jumlah)'
                , 'riwayat_pembelian'
                , $whereQuery
                , $bindParamWhere
            );
            $sellingQty = Database::selectSingleResult(
                'pratama'
                , 'SUM(Jumlah)'
                , 'detail_nota_penjualan'
                , $whereQuery
                , $bindParamWhere
            );
            $remaining = $buyingQty-$sellingQty;
            $row['Jumlah'] = $remaining;
            if($remaining>0)
            {
                Database::update('pratama', 'data_barang', ['Jumlah'=>$remaining,'Hide'=>0], $whereQuery, $bindParamWhere);
                continue;
            }

            $sellData = new PenjualanPratama('', 1, $row['NamaBarang']);
            if ($sellData->totalRows() < 1) { //Haven't been sold yet
                $this->delete($row['NamaBarang']);
                continue;
            }
            Database::update('pratama', 'data_barang', ['Jumlah'=>$remaining], $whereQuery, $bindParamWhere);
        }
    }

    public function delete($name) {
        $indexToBeDeleted = $this->findIndexOf($name);
        if($indexToBeDeleted < 0) {
            return;
        }
        Database::delete('pratama', 'data_barang', 'NamaBarang=:NamaBarang', ["NamaBarang"=>$name]);
        unset($this->rows[$indexToBeDeleted]);
        $this->rows = array_values($this->rows);
    }

    private function findIndexOf($name) {
        $wantedIndex = -1;
        foreach ($this->rows as $index => $row) {
            if(strcmp($row['NamaBarang'],$name) === 0) {
                $wantedIndex = $index;
                break;
            }
        }
        return $wantedIndex;
    }

    public function insert($name, $unit, $price=0) {
        $newRow = ['NamaBarang'=>$name, 'Unit'=>$unit, 'Harga'=>$price, 'Jumlah'=>0, 'Hide'=>0];
        Database::addRows(
            'pratama'
            , 'data_barang'
            , $newRow
            );
        $this->rows[] = $newRow;
    }
}
