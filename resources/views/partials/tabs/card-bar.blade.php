@if($variant === 'navbar')
    <x-tabs.navbar :$tabs :$currentTab :$queryAs :$ariaLabel />
@elseif($variant === 'segmented')
    <x-tabs :$tabs :$currentTab :$queryAs :$ariaLabel variant="segmented" />
@else
    <x-tabs :$tabs :$currentTab :$queryAs :$ariaLabel />
@endif
