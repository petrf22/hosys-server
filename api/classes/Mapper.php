<?php
abstract class Mapper {
    protected $db;
    public function __construct($db) {
        $this->db = $db;
    }

    abstract protected function getTableName();
    abstract protected function getColumnNames();
    abstract protected function getIdName();
    abstract protected function fromRow($row);

    public function findAll($offset, $max) {
        $sql = sprintf("SELECT %s FROM %s ORDER BY %s LIMIT :offset, :max", $this->getColumnNames(), $this->getTableName(), $this->getIdName());
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':max', $max > 100 ? 100 : $max, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];

        while($row = $stmt->fetch()) {
            array_push($results, $this->fromRow($row));
        }

        return $results;
    }

    public function findById($id) {
        $sql = sprintf("SELECT %s FROM %s WHERE %s = :%s", $this->getColumnNames(), $this->getTableName(), $this->getIdName(), $this->getIdName());
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':' . $this->getIdName(), $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return $this->fromRow($row);
        } else {
            return null;
        }
    }

    public function existsById($id) {
        $sql = sprintf("SELECT count(*) as pocet FROM %s WHERE %s = :%s", $this->getTableName(), $this->getIdName(), $this->getIdName());
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':' . $this->getIdName(), $id, PDO::PARAM_INT);
        $stmt->execute();

        return boolval($stmt->fetchColumn());
    }
}