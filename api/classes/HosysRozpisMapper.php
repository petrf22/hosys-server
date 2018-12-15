<?php
class HosysRozpisMapper extends Mapper
{
    protected function getTableName() {
        return 'hosys_rozpis';
    }

    protected function getColumnNames() {
        return 'hosys_rozpis_id, hosys_soutez_id, status_row, den_title, den, datum_title, datum, cas_title, cas, ' .
               'stadion_title, stadion, soutez_title, soutez, cislo_title, cislo, domaci_title, domaci, domaci_zkr_title, ' .
               'domaci_zkr, hoste, hoste_title, hoste_zkr_title, hoste_zkr, status, zmena, vlozeno';
    }

    protected function getIdName() {
        return 'hosys_rozpis_id';
    }

    protected function fromRow($row) {
        return HosysRozpisEntity::fromRow($row);
    }
}
