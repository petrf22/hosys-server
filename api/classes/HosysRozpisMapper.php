<?php
class HosysRozpisMapper extends Mapper
{
    protected function getTableName() {
        return 'hosys_rozpis';
    }

    protected function getColumnNames() {
        return 'hosys_rozpis_id, hosys_soutez_id, datum_od, datum_do, status_row, den_title, den, datum_title, datum, cas_title, cas, ' .
               'stadion_title, stadion, soutez_title, soutez, cislo_title, cislo, domaci_title, domaci, domaci_zkr_title, ' .
               'domaci_zkr, hoste, hoste_title, hoste_zkr_title, hoste_zkr, status, zmena, vlozeno';
    }

    protected function getIdName() {
        return 'hosys_rozpis_id';
    }

    protected function fromRow($row) {
        return HosysRozpisEntity::fromRow($row);
    }

    public function findBySoutez($soutez, $dayMin, $dayMax) {
        $sql = sprintf("SELECT %s FROM %s WHERE hosys_soutez_id = :soutez " .
                       " AND datum_od >= adddate(curdate(), :dayMin) " .
                       " AND datum_do <= adddate(curdate(), :dayMax) " .
                       "ORDER BY datum_od, %s", $this->getColumnNames(), $this->getTableName(), $this->getIdName());

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':soutez', $soutez, PDO::PARAM_STR);
        $stmt->bindParam(':dayMin', $dayMin, PDO::PARAM_INT);
        $stmt->bindParam(':dayMax', $dayMax, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];

        while($row = $stmt->fetch()) {
            array_push($results, $this->fromRow($row));
        }

        return $results;
    }
}
