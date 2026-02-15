@php
    use MathieuBretaud\FilamentMessenger\Filament\Pages\Messages;
    use MathieuBretaud\FilamentMessenger\Enums\MediaCollectionType;
    use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;
@endphp
@props(['selectedConversation'])
<div wire:poll.visible.{{ $pollInterval }}="loadConversations"
     style="--col-span-default: span 1 / span 1; height: inherit"
     class="col-[--col-span-default] lg:col-span-2 bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10 p-6 @if($selectedConversation) hidden lg:block @endif">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-6 items-center justify-between">
            <div class="flex items-center gap-3">
                <p class="text-lg font-bold">{{__('filament-messenger::messages.inbox')}}</p>
                @if ($this->unreadCount() > 0)
                    <x-filament::badge>
                        {{ $this->unreadCount() }}
                    </x-filament::badge>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <x-filament::button
                    icon="heroicon-o-magnifying-glass"
                    color="gray"
                    x-on:click="$dispatch('open-modal', { id: 'search-conversation' })">
                    {{ __('filament-messenger::messages.search') }}
                </x-filament::button>
                {{ $this->createConversation }}
            </div>
        </div>
        <div class="flex ">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$activeTab === 'new'"
                    class="new"
                    wire:click="$set('activeTab', 'new')">
                    <div class="flex items-center gap-2">
                        {{ __('filament-messenger::messages.new') }}
                        @if ($this->newUnreadCount() > 0)
                            <x-filament::badge color="danger" size="sm">
                                {{ $this->newUnreadCount() }}
                            </x-filament::badge>
                        @endif
                    </div>
                </x-filament::tabs.item>
                <x-filament::tabs.item
                    :active="$activeTab === InboxStatus::IN_PROGRESS->value"
                    class="in-progress"
                    wire:click="$set('activeTab', '{{ InboxStatus::IN_PROGRESS->value }}')">
                    <div class="flex items-center gap-2">
                        {{ InboxStatus::IN_PROGRESS->label() }}
                        @if ($this->inProgressUnreadCount() > 0)
                            <x-filament::badge :color="InboxStatus::IN_PROGRESS->color()" size="sm">
                                {{ $this->inProgressUnreadCount() }}
                            </x-filament::badge>
                        @endif
                    </div>
                </x-filament::tabs.item>
                <x-filament::tabs.item
                    :active="$activeTab === InboxStatus::TREATED->value"
                    class="treated"
                    wire:click="$set('activeTab', '{{ InboxStatus::TREATED->value }}')">
                    <div class="flex items-center gap-2">
                        {{ InboxStatus::TREATED->label() }}
                        @if ($this->treatedUnreadCount() > 0)
                            <x-filament::badge :color="InboxStatus::TREATED->color()" size="sm">
                                {{ $this->treatedUnreadCount() }}
                            </x-filament::badge>
                        @endif
                    </div>
                </x-filament::tabs.item>
                <x-filament::tabs.item
                    :active="$activeTab === 'sent'"
                    class="sent"
                    wire:click="$set('activeTab', 'sent')">
                    <div class="flex items-center gap-2">
                        {{ __('filament-messenger::messages.sent') }}
                        @if ($this->sentUnreadCount() > 0)
                            <x-filament::badge color="info" size="sm">
                                {{ $this->sentUnreadCount() }}
                            </x-filament::badge>
                        @endif
                    </div>
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    </div>
    <livewire:search/>

    <!-- Inbox : Start -->
    <div @style([
            'height: calc(100% - 150px)' => $this->conversations->count() > 0
        ])
        @class([
            'flex-1 overflow-y-auto' => $this->conversations->count() > 0,
        ])
        x-data="{
            init() {
                this.$el.addEventListener('scroll', () => {
                    if (this.$el.scrollTop + this.$el.clientHeight >= this.$el.scrollHeight - 100) {
                        $wire.loadMore();
                    }
                });
            }
        }"
    >
        @if ($this->conversations->count() > 0)
            <div class="grid w-full pb-9">
                @foreach ($this->conversations as $conversation)
                    @php
                        $latestMessage = $conversation->latestMessage();
                        $isUnread = $latestMessage && !in_array(auth()->id(), $latestMessage->read_by ?? []);
                    @endphp
                    <a wire:key="{{ $conversation->id }}" wire:navigate
                       href="{{ Messages::getUrl(tenant: filament()->getTenant()) . '/' . $conversation->id . '?tab=' . $activeTab }}"
                        @class([
                            'p-2 rounded-xl w-full mb-2',
                            'hover:bg-gray-100 hover:bg-gray-100 dark:hover:bg-white/10' => $conversation->id != $selectedConversation?->id,
                            'bg-gray-100 dark:bg-white/10 dark:text-white' => $conversation->id == $selectedConversation?->id,
                            'bg-gray-100 dark:bg-white/10' => $isUnread
                        ])>
                        <div class="grid grid-cols-[--cols-default] lg:grid-cols-[--cols-lg]"
                             style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-lg: repeat(6, minmax(0, 1fr));">
                            <div style="--col-span-default: span 5 / span 5;" class="col-[--col-span-default]">
                                <div class="flex gap-2">
                                    @php
                                        $otherUserName = $conversation->other_users->first()?->name ?? $conversation->inbox_title;
                                        $avatarSrc = "https://ui-avatars.com/api/?name=" . urlencode($otherUserName);
                                        $avatarAlt = $otherUserName;
                                    @endphp
                                    <x-filament::avatar
                                        src="{{ $avatarSrc }}"
                                        alt="{{ $avatarAlt }}"
                                        size="lg"/>
                                    <div class="overflow-hidden">
                                        <div class="flex items-center gap-2">
                                            @if ($isUnread)
                                                <span class="flex-shrink-0 w-2 h-2 bg-danger-600 rounded-full"></span>
                                            @endif
                                            <p
                                                @class([
                                                    'text-sm truncate',
                                                    'font-bold text-gray-900 dark:text-white' => $isUnread,
                                                    'font-semibold' => !$isUnread
                                                ])
                                            >{{ $conversation->inbox_title }}</p>
                                            @if($conversation->status)
                                                <x-filament::badge
                                                    :color="$conversation->status->displayColor($conversation)"
                                                    size="sm">
                                                    {{ $conversation->status->displayLabel($conversation) }}
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        @if($latestMessage)
                                            @php
                                                $latestMessageMedia = $latestMessage->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value);
                                            @endphp
                                            <p
                                                @class([
                                                    'text-sm truncate dark:text-gray-400',
                                                    'text-gray-600' => !$isUnread,
                                                    'font-semibold text-gray-900 dark:text-white' => $isUnread
                                                ])
                                            >
                                                <span class="font-bold">
                                                    {{ $latestMessage->user_id == auth()->id() ? __('filament-messenger::messages.you') . ':' : $latestMessage->sender->name . ':' }}
                                                </span>
                                                @if ($latestMessageMedia->count() > 0)
                                                    {{ $latestMessageMedia->count() > 1 ? __('filament-messenger::messages.attachments') : __('filament-messenger::messages.attachment') }}
                                                @else
                                                    {{ $latestMessage->message }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
                                <p
                                    @class([
                                        'text-sm text-end',
                                        'font-light text-gray-600 dark:text-gray-500' => !$isUnread,
                                        'font-semibold text-gray-900 dark:text-gray-400' => $isUnread
                                    ])
                                >
                                    {{ \Carbon\Carbon::parse($conversation->updated_at)->setTimezone(config('filament-messenger.timezone', 'app.timezone'))->locale('fr')->shortAbsoluteDiffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            @if ($hasMorePages)
                <div class="flex justify-center py-4 mb-6">
                    <div wire:loading.delay wire:target="loadMore">
                        <x-filament::loading-indicator class="h-6 w-6"/>
                    </div>
                </div>
            @endif
        @else
            <div class="flex flex-col items-center justify-center h-full p-3">
                <div class="p-3 mb-4 bg-gray-100 rounded-full dark:bg-gray-500/20">
                    <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-gray-500 dark:text-gray-400"/>
                </div>
                <p class="text-base text-center text-gray-600 dark:text-gray-400">
                    {{__('filament-messenger::messages.no_conversation_yet')}}
                </p>
            </div>
        @endif
    </div>
    <!-- Inbox : End -->
    <x-filament-actions::modals/>
</div>
