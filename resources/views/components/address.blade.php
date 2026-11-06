@aware(['disabled' => false])

@props([
    'form',
    'label' => null,
])

@php
    $disabled = (bool) $attributes->get('disabled', $disabled);
    $name = $form->getPropertyName();
    $postOffices = $form->postOffices();
    $districts = $form->showingDistricts ? $form->districts() : collect();
    $subdistricts = $form->showingDistricts ? $form->subdistricts() : collect();

    $cityPlaceholder = match (true) {
        blank($form->postcode) => __('common/inputs.city_needs_postcode'),
        $postOffices->isEmpty() => __('common/inputs.city_not_found'),
        default => __('common/inputs.city_placeholder'),
    };
@endphp

<div {{ $attributes->except('disabled')->class('space-y-6') }}>
    <div class="space-y-2">
        <x-input :label="$label ?? __('common/inputs.address')" :required="$form->required" :disabled="$disabled" wire:model="{{ $name }}.line_1" />
        <x-input :aria-label="__('common/inputs.address_line_2')" :disabled="$disabled" wire:model="{{ $name }}.line_2" />
        <x-input :aria-label="__('common/inputs.address_line_3')" :disabled="$disabled" wire:model="{{ $name }}.line_3" />
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <x-select
            :label="__('common/inputs.postcode')"
            :required="$form->required"
            :options="$form->postcodes()"
            :placeholder="__('common/inputs.postcode_placeholder')"
            :search-placeholder="__('common/inputs.postcode_search_placeholder')"
            :disabled="$disabled"
            search-model="{{ $name }}.postcodeSearch"
            search:inputmode="numeric"
            wire:model.live="{{ $name }}.postcode"
        />

        <x-select
            :label="__('common/inputs.city')"
            :required="$form->required"
            :options="$postOffices"
            :searchable="false"
            :placeholder="$cityPlaceholder"
            :disabled="$disabled || blank($form->postcode)"
            wire:model.live="{{ $name }}.post_office_id"
        />

        <x-input :label="__('common/inputs.state')" :value="$form->stateName()" disabled />
    </div>

    @if($form->showingDistricts)
        <div class="grid gap-6 sm:grid-cols-3">
            <x-select
                :label="__('common/inputs.district')"
                :required="$form->required && $form->districtsRequired"
                :options="$districts"
                :placeholder="__('common/inputs.district_placeholder')"
                :disabled="$disabled || $form->post_office_id === null"
                wire:model.live="{{ $name }}.district_id"
            />

            @if($subdistricts->isNotEmpty())
                <x-select
                    :label="__('common/inputs.subdistrict')"
                    :required="$form->required && $form->districtsRequired"
                    :options="$subdistricts"
                    :placeholder="__('common/inputs.subdistrict_placeholder')"
                    :disabled="$disabled"
                    wire:model.live="{{ $name }}.subdistrict_id"
                />
            @endif
        </div>
    @endif
</div>
