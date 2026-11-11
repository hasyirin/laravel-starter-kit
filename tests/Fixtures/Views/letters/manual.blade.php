<x-letter.page>
    <p>{{ $data->title }}</p>
    @if ($data->works)
        <p>{{ $data->works->contractor }}</p>
    @endif
    <p>{{ $extra->remark }}</p>
    @if ($extra->guarantor)
        <p>{{ $extra->guarantor->name }}</p>
    @endif
</x-letter.page>
