@php
    use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;
    use MathieuBretaud\FilamentMessenger\Enums\MediaCollectionType;
    use MathieuBretaud\FilamentMessenger\Filament\Pages\Messages;
@endphp
@props(['selectedConversation'])
<!-- Right Section (Chat Box) -->
<div
    style="--col-span-default: span 1 / span 1;"
    class="col-[--col-span-default] lg:col-span-3 bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10 overflow-hidden flex flex-col @if(!$selectedConversation) hidden lg:flex @endif">
    @if ($selectedConversation)
        <!-- Chat Header : Start -->
        <div class="flex items-center justify-between p-6">
            <div class="flex items-center gap-3 flex-1 overflow-hidden">
                <!-- Mobile back button -->
                <a href="{{ Messages::getUrl(tenant: filament()->getTenant()) . '?tab=' . request('tab', 'new') }}"
                   class="lg:hidden flex-shrink-0">
                    <x-filament::icon-button icon="heroicon-o-arrow-left"/>
                </a>
                @php
                    $otherUserName = $selectedConversation->other_users->first()?->name ?? $selectedConversation->inbox_title;
                    $avatarSrc = "https://ui-avatars.com/api/?name=" . urlencode($otherUserName);
                    $avatarAlt = $otherUserName;
                @endphp
                <x-filament::avatar
                    src="{{ $avatarSrc }}"
                    alt="{{ $avatarAlt }}"
                    size="lg"/>
                <div class="overflow-hidden flex-1">
                    <div class="flex items-center gap-2">
                        <p class="text-base font-bold truncate">{{ $selectedConversation->inbox_title }}</p>
                        @if($selectedConversation->status)
                            <x-filament::badge
                                :color="$selectedConversation->status->displayColor($selectedConversation)">
                                {{ $selectedConversation->status->displayLabel($selectedConversation) }}
                            </x-filament::badge>
                        @endif
                    </div>
                    @if ($selectedConversation->title)
                        <p class="text-base truncate">{{ $selectedConversation->other_users->pluck('name')->implode(', ') }}</p>
                    @endif
                </div>
            </div>
            @if($this->canManageInboxStatus())
                <div class="flex items-center gap-2 ml-3">
                    @foreach($this->getActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        </div>
        <!-- Chat Header : End -->
        <!-- Chat Box : Start -->
        <div wire:poll.visible.{{ $pollInterval }}="pollMessages" id="chatContainer"
             class="flex flex-col-reverse flex-1 p-6 overflow-y-auto border-t">
            @foreach ($conversationMessages as $index => $message)
                <div
                    @class([
                        'flex mb-3 px-2 items-end gap-3',
                        'justify-end' => $message->user_id === auth()->id(),
                        'justify-start' => $message->user_id !== auth()->id()
                    ]) wire:key="{{ $message->id }}">
                    @if ($message->user_id !== auth()->id())
                        <x-filament::avatar
                            src="https://ui-avatars.com/api/?name={{ urlencode($message->sender->name ?? 'User') }}"
                            alt="{{ $message->sender->name ?? 'User' }}"
                            size="sm"
                        />
                    @endif
                    <div>
                        @if ($message->user_id !== auth()->id())
                            <p class="text-xs mb-2">{{ $message->sender->name }}</p>
                        @endif
                        <div
                            @class([
                                'w-fit max-w-md p-3 rounded-xl mb-2',
                                'text-white bg-primary-600 dark:bg-primary-500' => $message->user_id === auth()->id(),
                                'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-500' => $message->user_id !== auth()->id(),
                            ])
                            @style([
                                'border-bottom-right-radius: 0' => $message->user_id === auth()->id(),
                                'border-bottom-left-radius: 0' => $message->user_id !== auth()->id(),
                            ])>
                            <div class="px-2">
                                @if ($message->message)
                                    <p class="text-sm">{!! nl2br($message->message) !!}</p>
                                @endif
                                @php
                                    $messageMedia = $message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value);
                                @endphp
                                @if ($messageMedia->isNotEmpty())
                                    @foreach ($messageMedia as $index => $media)
                                        <div
                                            wire:click="downloadAttachment('{{ $media->getPath() }}', '{{ $media->file_name }}')"
                                            @class([
                                                'flex items-center gap-3 p-3 my-2 rounded-lg group cursor-pointer',
                                                'bg-gray-200 dark:bg-gray-600' => $message->user_id !== auth()->id(),
                                                'bg-primary-500 dark:bg-primary-400' => $message->user_id === auth()->id()
                                            ])
                                        >
                                            <div
                                                @class([
                                                    'p-2 rounded-full',
                                                    'bg-gray-100 dark:bg-gray-500' => $message->user_id !== auth()->id(),
                                                    'bg-primary-600 group-hover:bg-primary-700 group-hover:dark:bg-primary-900' => $message->user_id === auth()->id()
                                                ])
                                            >
                                                @php
                                                    $icon = 'heroicon-o-x-circle';
                                                    if($this->validateImage($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-photo';
                                                    }

                                                    if ($this->validateDocument($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-paper-clip';
                                                    }

                                                    if ($this->validateVideo($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-video-camera';
                                                    }

                                                    if ($this->validateAudio($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-speaker-wave';
                                                    }
                                                @endphp
                                                <x-filament::icon icon="{{ $icon }}" class="w-4 h-4"/>
                                            </div>
                                            <p class="text-sm font-medium text-white dark:text-white hover:text-gray-700 dark:hover:text-gray-300 underline decoration-2 underline-offset-2">
                                                {{ __('avad/common.download_document') }}
                                            </p>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <p
                            @class([
                                'text-xs',
                                'text-end' => $message->user_id === auth()->id(),
                                'text-start' => $message->user_id !== auth()->id()
                            ])>
                            @php
                                $createdAt = \Carbon\Carbon::parse($message->created_at)->setTimezone(config('filament-messenger.timezone', 'app.timezone'))->locale('fr');

                                if ($createdAt->isToday()) {
                                    $date = $createdAt->format('H:i');
                                } else {
                                    $date = $createdAt->format('d/m/Y H:i');
                                }
                            @endphp
                            {{ $date }}
                        </p>
                    </div>
                </div>
                @php
                    $nextMessage = $conversationMessages[$index + 1] ?? null;
                    $nextMessageDate = $nextMessage ? \Carbon\Carbon::parse($nextMessage->created_at)->setTimezone(config('filament-messenger.timezone', 'app.timezone'))->format('Y-m-d') : null;
                    $currentMessageDate = \Carbon\Carbon::parse($message->created_at)->setTimezone(config('filament-messenger.timezone', 'app.timezone'))->format('Y-m-d');
                    $showDateBadge = $currentMessageDate !== $nextMessageDate;
                @endphp
                @if ($showDateBadge)
                    <div class="flex justify-center my-6">
                        <x-filament::badge>
                            {{ \Carbon\Carbon::parse($message->created_at)->setTimezone(config('filament-messenger.timezone', 'app.timezone'))->locale('fr')->isoFormat('D MMMM YYYY') }}
                        </x-filament::badge>
                    </div>
                @endif
            @endforeach
            @if ($this->paginator->hasMorePages())
                <div x-intersect="$wire.loadMessages">
                    <div
                        class="w-full py-6 text-center text-gray-900">{{__('filament-messenger::messages.getting_more_messages')}}</div>
                </div>
            @endif
        </div>
        <!-- Chat Box : End -->
        <!-- Chat Input : Start -->
        <div class="w-full p-6 border-t">
            <form wire:submit="sendMessage" class="flex items-end justify-between w-full gap-4">
                <div class="w-full max-h-96 overflow-y-auto p-1">
                    {{ $this->form }}
                </div>
                <div class="p-1">
                    <x-filament::button type="submit" icon="heroicon-o-paper-airplane"
                                        :disabled="$this->validateMessage()">{{ __('filament-messenger::messages.send') }}</x-filament::button>
                </div>
            </form>
            <x-filament-actions::modals/>
        </div>
        <!-- Chat Input : End -->
    @else
        <div class="flex flex-col items-center justify-center h-full p-3">
            <div class="p-3 mb-4 bg-gray-100 rounded-full dark:bg-gray-500/20">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-gray-500 dark:text-gray-400"/>
            </div>
            <p class="text-base text-center text-gray-600 dark:text-gray-400">
                {{__('filament-messenger::messages.no_conversation_selected')}}
            </p>
        </div>
    @endif
</div>
@script
<script>
    $wire.on('chat-box-scroll-to-bottom', () => {

        chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTo({
            top: chatContainer.scrollHeight,
            behavior: 'smooth',
        });

        setTimeout(() => {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 400);
    });
</script>
@endscript
