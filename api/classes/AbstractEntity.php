<?php
abstract class AbstractEntity
{
    protected function isSetVal(array $data, $key) {
        return isset($data) && isset($key) && isset($data[$key]);
    }

    protected function getValue($key, array $data, $default = null) {
        return $this->isSetVal($data, $key) ? $data[$key] : $default;
    }

    protected function getIntValue($key, array $data, $default = null) {
        $val = $this->getValue($key, $data, $default);

        return $val != null ? intval($val) : $val;
    }

    protected function getBoolValue($key, array $data, $default = null) {
        $val = $this->getValue($key, $data, $default);

        return $val != null ? boolval($val) : $val;
    }

    protected function updateValue(array $jsonData, $key, &$refLocalVar) {
        if ($this->isSetVal($jsonData, $key)) {
            $refLocalVar = $jsonData[$key];
        }
    }

    protected function updateStrValue(array $jsonData, $key, &$refLocalVar) {
        if ($this->isSetVal($jsonData, $key)) {
            $refLocalVar = trim(strval($jsonData[$key]));
        }
    }

    protected function updateIntValue(array $jsonData, $key, &$refLocalVar) {
        if ($this->isSetVal($jsonData, $key)) {
            $refLocalVar = intval($jsonData[$key]);
        }
    }

    protected function updateBoolValue(array $jsonData, $key, &$refLocalVar) {
        if ($this->isSetVal($jsonData, $key)) {
            $refLocalVar = boolval($jsonData[$key]);
        }
    }
}
