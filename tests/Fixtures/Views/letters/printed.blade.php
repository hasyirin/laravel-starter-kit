<x-letter :letter="$letter">
    <x-letter.recipient>{{ $data->organizationName }}</x-letter.recipient>
    <x-letter.subject>{{ $extra->remark }}</x-letter.subject>
    <x-letter.item>First point</x-letter.item>
    <x-letter.item>Second point</x-letter.item>
    <x-letter.signature :letter="$letter" />
    <x-letter.carbon-copies :letter="$letter" />
</x-letter>
