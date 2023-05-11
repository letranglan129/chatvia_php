<?php

namespace App\Libraries;

class Ultis
{
    public static function divideFullName($name)
    {
        if (is_string($name)) {
            $parts = explode(' ', $name);
            $lastname = array_pop($parts);
            $firstname = implode(' ', $parts);

            return [
                'firstname' => $firstname,
                'lastname' => $lastname,
            ];
        }
    }

    public static function compactName($name)
    {
        $nameDivided = Ultis::divideFullName($name);

        try {
            if ($nameDivided != null && count($nameDivided) > 1) {
                return  $nameDivided['firstname'][0] . $nameDivided['lastname'][0];
            }
        } catch (\Throwable $th) {
            return  $nameDivided['lastname'][0];
        }

        return '';
    }

    public static function array_sort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }
}
