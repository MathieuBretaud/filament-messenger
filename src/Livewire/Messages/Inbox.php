<?php

namespace MathieuBretaud\FilamentMessenger\Livewire\Messages;

use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;
use MathieuBretaud\FilamentMessenger\Livewire\Traits\CanMarkAsRead;
use MathieuBretaud\FilamentMessenger\Livewire\Traits\CanValidateFiles;
use MathieuBretaud\FilamentMessenger\Livewire\Traits\HasPollInterval;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Inbox extends Component implements HasActions, HasForms
{
    use CanMarkAsRead, CanValidateFiles, HasPollInterval, InteractsWithActions, InteractsWithForms;

    public $conversations;

    public $selectedConversation;

    #[Url(as: 'tab', keep: true)]
    public string $activeTab = 'new';

    public int $page = 1;

    public int $perPage = 20;

    public bool $hasMorePages = true;

    /**
     * Initialize the component.
     *
     * This method is called when the component is mounted,
     * and is used to set the poll interval and load the conversations.
     */
    public function mount(): void
    {
        $this->setPollInterval();
        $this->loadConversations();
    }

    /**
     * Listen for tab changes from the status badges
     *
     * @param  string  $tab  The new active tab value
     */
    #[On('tab-changed')]
    public function updateActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->page = 1;
        $this->hasMorePages = true;
        $this->loadConversations();
    }

    /**
     * Get the count of unread messages for the authenticated user.
     *
     * This method queries the Inbox model to find conversations
     * including the user and checks for messages not read by the user.
     *
     * @return int The number of unread messages.
     */
    public function unreadCount(): int
    {
        return Auth::user()
            ->allConversations()
            ->whereHas('messages', function ($query) {
                $query->whereJsonDoesntContain('read_by', Auth::id());
            })
            ->count();
    }

    /**
     * Get the count of unread new messages.
     * Includes: MESSAGE status where user is recipient OR IN_PROGRESS where last message is not from current user.
     */
    public function newUnreadCount(): int
    {
        return Auth::user()
            ->allConversations()
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('creator_id', '!=', Auth::id())
                        ->where('status', InboxStatus::MESSAGE->value);
                })
                    ->orWhere(function ($sub) {
                        $sub->where('status', InboxStatus::IN_PROGRESS->value)
                            ->whereRaw('(SELECT user_id FROM messages WHERE inbox_id = inboxes.id ORDER BY created_at DESC LIMIT 1) != ?', [Auth::id()]);
                    });
            })
            ->whereHas('messages', function ($query) {
                $query->whereJsonDoesntContain('read_by', Auth::id());
            })
            ->count();
    }

    /**
     * Get the count of unread sent messages (MESSAGE status where user is creator).
     */
    public function sentUnreadCount(): int
    {
        return Auth::user()
            ->allConversations()
            ->where('creator_id', Auth::id())
            ->where('status', InboxStatus::MESSAGE->value)
            ->whereHas('messages', function ($query) {
                $query->whereJsonDoesntContain('read_by', Auth::id());
            })
            ->count();
    }

    /**
     * Get the count of unread in-progress messages where the last message is from the current user.
     */
    public function inProgressUnreadCount(): int
    {
        return Auth::user()
            ->allConversations()
            ->where('status', InboxStatus::IN_PROGRESS->value)
            ->whereRaw('(SELECT user_id FROM messages WHERE inbox_id = inboxes.id ORDER BY created_at DESC LIMIT 1) = ?', [Auth::id()])
            ->whereHas('messages', function ($query) {
                $query->whereJsonDoesntContain('read_by', Auth::id());
            })
            ->count();
    }

    /**
     * Get the count of unread treated messages.
     */
    public function treatedUnreadCount(): int
    {
        return Auth::user()
            ->allConversations()
            ->where('status', InboxStatus::TREATED->value)
            ->whereHas('messages', function ($query) {
                $query->whereJsonDoesntContain('read_by', Auth::id());
            })
            ->count();
    }

    /**
     * Load the conversations for the current user.
     *
     * This method is called when the poll interval is reached,
     * and is used to refresh the conversations list.
     * Filters conversations based on active tab.
     */
    #[On('refresh-inbox')]
    public function loadConversations(): void
    {
        $query = Auth::user()->allConversations();

        match ($this->activeTab) {
            'new' => $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('creator_id', '!=', Auth::id())
                        ->where('status', InboxStatus::MESSAGE->value);
                })
                    ->orWhere(function ($sub) {
                        $sub->where('status', InboxStatus::IN_PROGRESS->value)
                            ->whereRaw('(SELECT user_id FROM messages WHERE inbox_id = inboxes.id ORDER BY created_at DESC LIMIT 1) != ?', [Auth::id()]);
                    });
            }),
            'sent' => $query->where('creator_id', Auth::id())
                ->where('status', InboxStatus::MESSAGE->value),
            InboxStatus::IN_PROGRESS->value => $query
                ->where('status', InboxStatus::IN_PROGRESS->value)
                ->whereRaw('(SELECT user_id FROM messages WHERE inbox_id = inboxes.id ORDER BY created_at DESC LIMIT 1) = ?', [Auth::id()]),
            InboxStatus::TREATED->value => $query->where('status', InboxStatus::TREATED->value),
            default => $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('creator_id', '!=', Auth::id())
                        ->where('status', InboxStatus::MESSAGE->value);
                })
                    ->orWhere(function ($sub) {
                        $sub->where('status', InboxStatus::IN_PROGRESS->value)
                            ->whereRaw('(SELECT user_id FROM messages WHERE inbox_id = inboxes.id ORDER BY created_at DESC LIMIT 1) != ?', [Auth::id()]);
                    });
            }),
        };

        $this->conversations = $query
            ->with([
                'creator',
                'recipient',
                'messages' => function ($query) {
                    $query->latest()->limit(1)->with(['sender', 'media']);
                },
            ])
            ->take($this->page * $this->perPage)
            ->get();

        // Check if there are more pages
        $total = $query->count();
        $this->hasMorePages = ($this->page * $this->perPage) < $total;

        $this->markAsRead();
    }

    /**
     * Load more conversations when scrolling.
     */
    public function loadMore(): void
    {
        if ($this->hasMorePages) {
            $this->page++;
            $this->loadConversations();
        }
    }

    /**
     * Update conversations when tab changes
     */
    public function updatedActiveTab(): void
    {
        $this->page = 1;
        $this->hasMorePages = true;
        $this->loadConversations();
    }

    /**
     * Define the action for creating a new conversation.
     */
    public function createConversationAction(): Action
    {
        $userModel = config('auth.providers.users.model');

        return Action::make('createConversation')
            ->icon('heroicon-o-plus')
            ->label(__('filament-messenger::messages.new_message'))
            ->form([
                Forms\Components\TextInput::make('title')
                    ->label(__('filament-messenger::messages.subject'))
                    ->required(),
                Forms\Components\Select::make('recipient_id')
                    ->label(__('filament-messenger::messages.recipient'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => $userModel::query()
                        ->where('id', '!=', Auth::id())
                        ->where(function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($user) => [$user->id => $user->name])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => $userModel::find($value)?->name)
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->label(__('filament-messenger::messages.message'))
                    ->placeholder(__('filament-messenger::messages.write_message_placeholder'))
                    ->required()
                    ->autosize(),
            ])
            ->modalHeading(__('filament-messenger::messages.create_new_message'))
            ->modalSubmitActionLabel(__('filament-messenger::messages.send'))
            ->action(function (array $data) {
                $inbox = \MathieuBretaud\FilamentMessenger\Models\Inbox::create([
                    'title' => $data['title'],
                    'creator_id' => Auth::id(),
                    'recipient_id' => $data['recipient_id'],
                    'status' => InboxStatus::MESSAGE,
                ]);

                $inbox->messages()->create([
                    'message' => $data['message'],
                    'user_id' => Auth::id(),
                    'read_by' => [Auth::id()],
                    'read_at' => [now()],
                    'notified' => [Auth::id()],
                ]);

                redirect(\MathieuBretaud\FilamentMessenger\Filament\Pages\Messages::getUrl(['id' => $inbox->getKey()]).'?tab=sent');
            });
    }

    /**
     * Render the inbox view for the Livewire component.
     *
     * This method returns the view responsible for displaying
     * the inbox interface, which includes the list of conversations
     * and controls for interacting with them.
     */
    public function render(): Application|Factory|View|\Illuminate\View\View
    {
        return view('filament-messenger::livewire.messages.inbox');
    }
}
