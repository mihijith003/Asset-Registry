{{--
    A money amount coloured by sign for print views: green (.gain) when positive, red (.loss) when negative,
    "-" when unknown.   @include('partials.signed-amount', ['amount' => $row->gain])
--}}
@if ($amount === null)
    -
@else
    <span class="{{ $amount > 0 ? 'gain' : ($amount < 0 ? 'loss' : '') }}">{{ $amount > 0 ? '+' : '' }}{{ number_format($amount, 2) }}</span>
@endif
