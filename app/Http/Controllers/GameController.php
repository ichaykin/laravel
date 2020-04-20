<?php

namespace App\Http\Controllers;

use App\User;
use App\Round;
use App\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function client()
    {
        $round = Round::latest()->first();
        if (!$round) {
            return 'game not started, press F5';
        }

        if (Cookie::has('token')) {
            $token = Cookie::get('token');
            $ticket = Ticket::getByToken($token);
        } else {
            $ticket = new Ticket();
            $ticket->save();
        }

        if (!$ticket) {
            $ticket = new Ticket();
            $ticket->save();
        }

        $ticket->numbers = unserialize($ticket->numbers);

        return response()
            ->view('client', compact('ticket'))
            ->withCookie(Cookie::make('token', $ticket->token));
    }

    public function server(Request $request, $id = null)
    {
        if ($request->isMethod('POST')) {
            $round = new Round();
            $numbers = Round::select('number')->get()->pluck('number')->toArray();

            if (count($numbers) > 89) {
                return redirect('/server');
            }

            $number = rand(1,90);

            while(in_array($number, $numbers)) {
                $number = rand(1,90);
            }

            $round->number = $number;
            $round->save();
            //check winnings tickets
            $numbers[] = $number;
            $this->checkTickets($numbers, $round->round_id);

            return redirect('/server/');// . $round->round_id);
        }
        if ($id) {
            return 'вернуть 10 билетов с максимальным выйгрышом';
        } else {
            $round = Round::latest()->first();

            if (!$round) {
                $round = new Round();
                $round->number = rand(1,90);
                $round->save();
            }

            $tickets_count = Ticket::where('is_winning', false)->count();
            $bank = $tickets_count * 100 * 0.7;
            $profit = $tickets_count * 100 * 0.3;

            return view('server', compact('round', 'tickets_count', 'bank', 'profit'));
        }
    }

    private function checkTickets($numbers, $round_id)
    {
        $tickets = Ticket::where('is_winning', false)->get();
        $tickets_count = count($tickets);
        $bank = $tickets_count * 100 * 0.7;

        foreach ($tickets as $ticket_id => $ticket) {
            $tickets[$ticket_id]->numbers = unserialize($tickets[$ticket_id]->numbers);
        }

        //5 in a row
        $five_count = 0;
        foreach ($tickets as $ticket_id => $ticket) {
            foreach ($ticket->numbers as $field) {
                if ($this->checkRow($numbers, $field)) {
                    $tickets[$ticket_id]->is_winning = true;
                    $tickets[$ticket_id]->numbers = serialize($tickets[$ticket_id]->numbers);
                    $tickets[$ticket_id]->save();
                    $five_count++;
                    break;
                }
            }
        }

        if ($five_count > 0) {
            $per_ticket = $bank * 0.5 / $five_count;
            foreach ($tickets as $ticket_id => $ticket) {
                if ($ticket->is_winning) {
                    $this->createWinningTicket($round_id, $ticket->ticket_id, $per_ticket);
                    unset($tickets[$ticket_id]);
                }
            }
        }

        //15 in a field
        $tickets_count = count($tickets);
        $bank = $tickets_count * 100 * 0.7;

        $fifteen_count = 0;
        foreach ($tickets as $ticket_id => $ticket) {
            foreach ($ticket->numbers as $field) {
                if ($this->checkField($numbers, $field)) {
                    $tickets[$ticket_id]->is_winning = true;
                    $tickets[$ticket_id]->numbers = serialize($tickets[$ticket_id]->numbers);
                    $tickets[$ticket_id]->save();
                    $fifteen_count++;
                    break;
                }
            }
        }

        if ($fifteen_count > 0) {
            $per_ticket = $bank * 0.5 / $fifteen_count;
            foreach ($tickets as $ticket_id => $ticket) {
                if ($ticket->is_winning) {
                    $this->createWinningTicket($round_id, $ticket->ticket_id, $per_ticket);
                    unset($tickets[$ticket_id]);
                }
            }
        }

        //30 in ticket
        $tickets_count = count($tickets);
        $bank = $tickets_count * 100 * 0.7;

        $thirty_count = 0;
        foreach ($tickets as $ticket_id => $ticket) {
            if ($this->checkTicket($numbers, $ticket->numbers)) {
                $tickets[$ticket_id]->is_winning = true;
                $tickets[$ticket_id]->numbers = serialize($tickets[$ticket_id]->numbers);
                $tickets[$ticket_id]->save();
                $thirty_count++;
                break;
            }
        }

        if ($thirty_count > 0) {
            $per_ticket = $bank * 0.5 / $thirty_count;
            foreach ($tickets as $ticket_id => $ticket) {
                if ($ticket->is_winning) {
                    $this->createWinningTicket($round_id, $ticket->ticket_id, $per_ticket);
                    unset($tickets[$ticket_id]);
                }
            }
        }
    }

    private function checkRow($numbers, $field)
    {
        foreach ($field as $row) {
            if (count(array_intersect($row, $numbers)) == 5) {
                return true;
            }
        }
        return false;
    }

    private function checkField($numbers, $field)
    {
        if (count(array_intersect(Arr::flatten($field), $numbers)) == 15) {
            return true;
        }

        return false;
    }

    private function checkTicket($numbers, $fields)
    {
        if (count(array_intersect($numbers, Arr::flatten($fields))) == 30) {
            return true;
        }

        return false;
    }

    private function createWinningTicket($round_id, $ticket_id, $summa)
    {
        DB::insert('INSERT INTO winning_tickets VALUES (?, ?, ?, ?, ?)', [$round_id, $ticket_id, $summa, Carbon::now(), Carbon::now()]);
    }
}
