<p>Тур: {{ $round->round_id }}</p>
<p>Участников: {{ $tickets_count }}</p>
<p>Банк: {{ $bank }}</p>
<p>Прибыль: {{ $profit }}</p>
<form method="POST">
    @csrf
    <input type="submit" value="Следующий тур">
</form>
