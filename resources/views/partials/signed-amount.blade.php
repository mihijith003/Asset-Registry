{{--
    A money amount coloured by sign: green (.gain) when positive, red (.loss) when negative - in brackets, through
    money(), like every negative amount - and "-" when unknown.   @include('partials.signed-amount', ['amount' => $row->gain])
--}}
@if ($amount === null)
    -
@else
    <span class="{{ $amount > 0 ? 'gain' : ($amount < 0 ? 'loss' : '') }}">{{ money($amount) }}</span>
@endif
