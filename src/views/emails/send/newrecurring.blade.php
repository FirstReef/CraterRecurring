@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => ''])
    @if($data['company']['logo'])
        <img class="header-logo" src="{{asset($data['company']['logo'])}}" alt="{{$data['company']['name']}}">
    @else
        {{$data['company']['name']}}
    @endif
    @endcomponent
@endslot

{{-- Body --}}
<!-- Body here -->

{{-- Subcopy --}}
@slot('subcopy')
    @component('mail::subcopy')
        A new invoice <a href="{{ url('/admin/invoices/'.$new_invoice['id'].'/view') }}" style="color: #00B398;">{{ $new_invoice->invoice_number }}</a> has been created from invoice <a href="{{ url('/admin/invoices/'.$invoice['id'].'/view') }}" style="color: #00B398;">{{ $invoice->invoice_number }}</a> and sent to <b>{{ $new_invoice->user->name }}</b>
        @component('mail::button', ['url' => url('/admin/invoices/'.$new_invoice['id'].'/view')])
            View Invoice
        @endcomponent
    @endcomponent
@endslot

{{-- Footer --}}
@slot('footer')
    @component('mail::footer')
        Powered by <a class="footer-link" href="https://craterapp.com">Crater</a>
    @endcomponent
@endslot
@endcomponent
