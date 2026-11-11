<x-letter.page>
    <p>{{ $data->organizationName }}</p>
    @if ($data->works)
        <p>{{ $data->works->contractor }}</p>
    @endif
    <p>{{ $data->issuedOn->toDateString() }}</p>
</x-letter.page>
