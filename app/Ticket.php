<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $primaryKey = 'ticket_id';

    public function __construct(array $attributes = [])
    {
        $this->generateTicket();
        parent::__construct($attributes);
    }

    private function generateTicket() {
        $this->token = Str::random(80);
        $fields = [];
        $fields[] = $this->fillField();
        $fields[] = $this->fillField();

        $this->numbers = serialize($fields);
    }

    private function fillField()
    {
        $rows = 3;
        $columns = 9;
        $field = [];

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $columns; $j++) {
                $field[$i][$j] = null;
            }
        }

        for($i = 0; $i < $rows; $i++) {
            $el_count = 0;
            while ($el_count < 5) {
                $column = rand(0, $columns - 1);

                if (!$field[$i][$column]) {
                    $number = rand($column*10 + 1,($column+1)*10);

                    if (in_array($number, Arr::flatten($field))) {
                        continue;
                    }

                    $field[$i][$column] = $number;
                    $el_count++;
                }
            }
        }

        return $field;
    }

    public static function getByToken($token)
    {
        if ($token) {
            $ticket = Ticket::where('token', $token)->first();

            return $ticket;
        }
    }
}
