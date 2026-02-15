<x-filament-panels::page>
    <div class="grid grid-cols-[--cols-default] lg:grid-cols-[--cols-lg] fi-fo-component-ctn gap-6 overflow-hidden"
        style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-lg: repeat(5, minmax(0, 1fr)); height: calc(100vh - 8rem);">
        <livewire:inbox :selectedConversation="$selectedConversation" />
        <livewire:messages :selectedConversation="$selectedConversation" />
    </div>
</x-filament-panels::page>
