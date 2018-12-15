<?php
class HosysSoutezMapper extends Mapper
{
    protected function getTableName() {
        return 'hosys_soutez';
    }

    protected function getColumnNames() {
        return 'hosys_soutez_id, uroven, nazev, poradi';
    }

    protected function getIdName() {
        return 'hosys_soutez_id';
    }

    protected function fromRow($row) {
        return HosysSoutezEntity::fromRow($row);
    }

    public function findAll($offset, $max) {
        $sql = sprintf("SELECT %s FROM %s ORDER BY %s", $this->getColumnNames(), $this->getTableName(), 'poradi');
        $stmt = $this->db->prepare($sql);
        //$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        //$stmt->bindParam(':max', $max > 100 ? 100 : $max, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];

        while($row = $stmt->fetch()) {
            array_push($results, $this->fromRow($row));
        }

        return $results;
    }
}
