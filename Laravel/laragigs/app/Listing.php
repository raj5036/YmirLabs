<?php
    namespace App;

    class Listing {
        public static function all () {
            return [
                [
                    "id"=>1,
                    "name"=>"List1",
                    "content"=>"content1"
                ],
                [
                    "id"=>2,
                    "name"=>"List2",
                    "content"=>"content2"
                ]
            ];
        }

        public static function find ($id) {
            $listings = self::all();

            foreach ($listings as $listing) {
                if ($listing['id'] == $id) {
                    return $listing;
                }
            }
        }
    }
?>
