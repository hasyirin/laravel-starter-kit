@if($variant === 'navbar')
    <x-tabs.navbar :$tabs :$currentTab :$queryAs :$ariaLabel />
@else
    <x-tabs :$tabs :$currentTab :$queryAs :$ariaLabel :variant="$variant === 'underline' ? null : $variant" />
@endif
