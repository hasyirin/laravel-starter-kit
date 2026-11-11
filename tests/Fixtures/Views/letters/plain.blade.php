<x-letter.page>
    <p>{{ $title }}</p>
    @isset($date)
        <p>{{ App\Domain\User\Support\Preferences::current()->calendarDate($date) }}</p>
    @endisset
</x-letter.page>
