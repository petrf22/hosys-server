<?php
class HosysSoutezTabMapper extends Mapper
{
    protected function getTableName() {
        return 'hosys_soutez_tab';
    }

    protected function getColumnNames() {
        return 'hosys_soutez_tab_id, hosys_soutez_id, html_tabulka, html_soutez, zmeneno';
    }

    protected function getIdName() {
        return 'hosys_soutez_tab_id';
    }

    protected function fromRow($row) {
        return HosysSoutezTabEntity::fromRow($row);
    }

    public function findByHosysSoutezId($hosysSoutezId) {
        $sql = sprintf("SELECT %s FROM %s WHERE hosys_soutez_id = :hosys_soutez_id", $this->getColumnNames(), $this->getTableName());
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':hosys_soutez_id', $hosysSoutezId, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return $this->fromRow($row);
        } else {
            return null;
        }
    }

}
