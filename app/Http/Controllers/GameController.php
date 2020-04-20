<?php

namespace App\Http\Controllers;

use App\User;
use App\Round;
use App\Ticket;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;

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
            //$this->checkTickets($numbers, $round->round_id);

            return redirect('/server/' . $round->round_id);
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
        $profit = $tickets_count * 100 * 0.3;

        foreach ($tickets as $ticket_id => $ticket) {
            $tickets[$ticket_id]->numbers = unserialize($tickets[$ticket_id]->numbers);
        }
            //5 in a row
        $five_count = 0;
        foreach ($tickets as $ticket_id => $ticket) {
            foreach ($ticket->numbers as $field) {
                if ($this->checkRow($numbers, $field)) {
                    $tickets[$ticket_id]->is_winning = true;
                    $tickets[$ticket_id]->save();
                    $five_count++;
                    break;
                }
            }
        }
        $per_ticket = $bank / $five_count;
        foreach ($tickets as $ticket_id => $ticket) {
            if ($ticket->is_winning) {
                $this->createWinningTicket($round_id, $ticket->token, $per_ticket);
                unset($tickets[$ticket_id]);
            }
        }
            //15 in a field

            //30 in ticket
//            if (array_intersect($numbers, $tickets[$ticket_id]->numbers)) {
//                var_dump('30 in ticket');
//            }

    }

    private function checkRow($numbers, $field)
    {

    }

    private function checkField($numbers, $field)
    {

    }

    private function checkTicket($numbers, $ticket)
    {

    }

    private function createWinningTicket($round_id, $ticket_id, $summa)
    {
        DB::insert('INSERT INTO winning_tickets VALUES (?, ?, ?)', [$round_id, $ticket_id, $summa]);
    }
}
