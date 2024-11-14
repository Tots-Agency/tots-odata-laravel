<?php

namespace Tots\Odata\Parsers;

use Illuminate\Database\Eloquent\Model;

trait ModelParser
{
    public function addSelectRawByModel($subject): static
    {
        if(!is_subclass_of($subject, Model::class)) {
            return $this;
        }

        $fields = $subject::$resourceFields;
        $table = (new $subject)->getTable();

        foreach($fields as $field) {
            $this->selectRaw($table . '.' . $field . ' AS ' . $table . '_' . $field);
        }

        return $this;
    }

    public function convertToRelationModel(\Illuminate\Contracts\Pagination\LengthAwarePaginator $result, $relationKey, $subject): static
    {
        if(!is_subclass_of($subject, Model::class)) {
            return $this;
        }

        $table = (new $subject)->getTable();
        $fields = $subject::$resourceFields;
        $items = $result->items();

        foreach($items as $item) {
            $newObject = new $subject();
            foreach($fields as $field) {
                $newObject->{$field} = $item->{$table . '_' . $field};
            }

            $item->setRelation($relationKey, $newObject);
        }

        return $this;
    }
}
