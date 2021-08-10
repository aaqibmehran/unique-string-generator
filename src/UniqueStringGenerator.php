<?php

namespace Aaqib\UniqueStringGenerator;

use Illuminate\Support\Facades\DB, Exception;

class UniqueStringGenerator
{
    private function getFieldType($table, $field)
    {
        $connection = config('database.default');
        $driver = DB::connection($connection)->getDriverName();
        $database = DB::connection($connection)->getDatabaseName();

        if ($driver == 'mysql') {
            $sql = 'SELECT column_name AS "column_name",data_type AS "data_type",column_type AS "column_type" FROM information_schema.columns ';
            $sql .= 'WHERE table_schema=:database AND table_name=:table';
        } else {
            // column_type not available in postgres SQL
            // table_catalog is database in postgres
            $sql = 'SELECT column_name AS "column_name",data_type AS "data_type" FROM information_schema.columns ';
            $sql .= 'WHERE table_catalog=:database AND table_name=:table';
        }

        $rows = DB::select($sql, ['database' => $database, 'table' => $table]);
        $fieldType = null;
        $fieldLength = null;

        foreach ($rows as $col) {
            if ($field == $col->column_name) {
                $fieldType = $col->data_type;
                if ($driver == 'mysql') {
                    //example: column_type int(11) to 11
                    preg_match("/(?<=\().+?(?=\))/", $col->column_type, $tblFieldLength);
                    $fieldLength = $tblFieldLength[0];
                } else {
                    //column_type not available in postgres SQL
                    $fieldLength = 32;
                }
                break;
            }
        }

        if ($fieldType == null) throw new Exception("$field not found in $table table");
        return ['type' => $fieldType, 'length' => $fieldLength];
    }

    private function generateCode($length){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function generate($configArr)
    {
        if (!array_key_exists('table', $configArr) || $configArr['table'] == '') {
            throw new Exception('Must need a table name');
        }
        if (!array_key_exists('length', $configArr) || $configArr['length'] == '') {
            throw new Exception('Must specify the length of String');
        }
        if (!array_key_exists('prefix', $configArr) || $configArr['prefix'] == '') {
            throw new Exception('Must specify a prefix of your String');
        }

        if (array_key_exists('where', $configArr)) {
            if (is_string($configArr['where']))
                throw new Exception('where clause must be an array, you provided string');
            if (!count($configArr['where']))
                throw new Exception('where clause must need at least an array');
        }

        $table = $configArr['table'];
        $field = array_key_exists('field', $configArr) ? $configArr['field'] : 'id';
        $prefix = $configArr['prefix'];
        $resetOnPrefixChange = array_key_exists('reset_on_prefix_change', $configArr) ? $configArr['reset_on_prefix_change'] : false;
        $length = $configArr['length'];

        $fieldInfo = (new self)->getFieldType($table, $field);
        $tableFieldType = $fieldInfo['type'];
        $tableFieldLength = $fieldInfo['length'];

        if (in_array($tableFieldType, ['int', 'integer', 'bigint', 'numeric']) && !is_numeric($prefix)) {
            throw new Exception("$field field type is $tableFieldType but prefix is string");
        }

        if ($length > $tableFieldLength) {
            throw new Exception('Generated ID length is bigger then table field length');
        }

        $prefixLength = strlen($configArr['prefix']);

        $idLength = $length - $prefixLength;

        try {
            $all_models = DB::table($table)->select($field)->get();
            $generated_code = (new self)->generateCode($idLength);
            while ($all_models->contains($generated_code)) {
                $generated_code = (new self)->generateCode($idLength);
            }

            if ($generated_code) {
                return $prefix.$generated_code;

            } else {
                return $prefix.$generated_code;
            }
        } catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}
