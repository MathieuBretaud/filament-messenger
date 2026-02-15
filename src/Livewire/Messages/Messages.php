<?php

namespace MathieuBretaud\FilamentMessenger\Livewire\Messages;

use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;
use MathieuBretaud\FilamentMessenger\Livewire\Traits\CanMarkAsRead;
use MathieuBretaud\FilamentMessenger\Livewire\Traits\HasPollInterval;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component implements HasActions, HasForms
{
    use CanMarkAsRead, HasPollInterval, InteractsWithActions, InteractsWithForms, WithPagination;

    public $selectedConversation;

    public $currentPage = 1;

    public Collection $conversationMessages;

    public ?array $data = [];

    public $inboxStatus;

    /**
     * Initialize the Messages component.
     *
     * This method is called when the component is mounted.
     * It sets the polling interval, fills the form state, and
     * if a conversation is selected, initializes the conversation
     * messages, loads existing messages, and marks them as read.
     */
    public function mount(): void
    {
        $this->setPollInterval();
        $this->form->fill();
        if ($this->selectedConversation) {
            $this->conversationMessages = collect();
            $this->loadMessages();
            $this->markAsRead();
            $this->dispatch('refresh-inbox');
            $this->inboxStatus = $this->selectedConversation->status?->value;
        }
    }

    /**
     * Poll for new messages in the selected conversation.
     *
     * This method retrieves messages that are newer than the
     * latest message currently loaded in the conversation.
     * If new messages are found, they are prepended to the
     * existing collection of conversation messages.
     */
    public function pollMessages(): void
    {
        $latestId = $this->conversationMessages->pluck('id')->first();
        $polledMessages = $this->selectedConversation->messages()
            ->with(['sender', 'media'])
            ->where('id', '>', $latestId)
            ->latest()
            ->get();

        if ($polledMessages->isNotEmpty()) {
            $this->conversationMessages = collect([
                ...$polledMessages,
                ...$this->conversationMessages,
            ]);
        }
    }

    /**
     * Load the next page of messages for the selected conversation.
     *
     * This method appends the messages from the next page to the
     * existing collection of conversation messages and increments
     * the current page number.
     */
    public function loadMessages(): void
    {
        $this->conversationMessages->push(...$this->paginator->getCollection());
        $this->currentPage = $this->currentPage + 1;
    }

    /**
     * Customize the form schema for the Messages component.
     *
     * This method defines the form schema used by the Messages component,
     * which includes support for file uploads and a message textarea.
     * The form state is stored in the 'data' property.
     *
     * - The 'attachments' field allows multiple file uploads and is
     *   conditionally visible based on the 'showUpload' property.
     * - The 'show_hide_upload' action toggles the visibility of the
     *   attachments upload field.
     * - The 'message' field is a textarea that supports live updates
     *   and automatically adjusts its height based on the content.
     *
     * @param  Schema  $form  The form instance.
     * @return Schema The customized form instance.
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->live()
                    ->hiddenLabel()
                    ->placeholder(__('filament-messenger::messages.write_message_placeholder'))
                    ->rows(1)
                    ->autosize(),
            ])->statePath('data');
    }

    /**
     * Sends a message with attachments in the selected conversation.
     *
     * This method retrieves the form state, including message content and attachments,
     * and saves the message to the database within a transaction. The message is then
     * prepended to the conversation messages collection. Attachments are processed and
     * added to the media collection. The form is reset, the conversation's updated
     * timestamp is refreshed, and the inbox is refreshed. If an exception occurs, a
     * notification is sent to inform the user of the error.
     *
     * @throws \Exception|\Throwable
     */
    public function sendMessage(): void
    {
        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data) {
                $newMessage = $this->selectedConversation->messages()->create([
                    'message' => $data['message'] ?? null,
                    'user_id' => Auth::id(),
                    'read_by' => [Auth::id()],
                    'read_at' => [now()],
                    'notified' => [Auth::id()],
                ]);

                $newMessage->load(['sender']);

                $this->conversationMessages->prepend($newMessage);

                $this->form->fill();

                $shouldTransitionToInProgress =
                    Auth::id() !== $this->selectedConversation->creator_id ||
                    $this->selectedConversation->status === InboxStatus::TREATED;

                if ($shouldTransitionToInProgress) {
                    $this->selectedConversation->status = InboxStatus::IN_PROGRESS;
                    $this->dispatch('tab-changed', InboxStatus::IN_PROGRESS->value);
                }

                $this->selectedConversation->updated_at = now();
                $this->selectedConversation->save();

                $this->dispatch('refresh-inbox');
            });
        } catch (\Exception $exception) {
            Notification::make()
                ->title(__('filament-messenger::messages.something_went_wrong'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Computes the paginator for the conversation messages.
     *
     * This method retrieves the latest messages for the selected conversation
     * and paginates them by 10 messages per page. The pagination starts at
     * the current page index.
     *
     * @return Paginator The paginator instance
     *                   for the conversation messages.
     */
    #[Computed()]
    public function paginator(): Paginator
    {
        return $this->selectedConversation->messages()
            ->with(['sender'])
            ->latest()
            ->paginate(10, ['*'], 'page', $this->currentPage);
    }

    /**
     * Download an attachment from the given file path and return it as a response.
     *
     * @param  string  $filePath  The file path of the attachment to download.
     * @param  string  $fileName  The file name to send with the attachment.
     * @return BinaryFileResponse The response containing the attachment.
     */
    public function downloadAttachment(string $filePath, string $fileName): BinaryFileResponse
    {
        return response()->download($filePath, $fileName);
    }

    /**
     * Check if the current user can manage inbox status.
     */
    #[Computed()]
    public function canManageInboxStatus(): bool
    {
        return config('filament-messenger.allow_status_management', true);
    }

    /**
     * Get status change actions based on current status.
     */
    protected function getActions(): array
    {
        if (! $this->canManageInboxStatus() || ! $this->selectedConversation) {
            return [];
        }

        $currentStatus = $this->selectedConversation->status;

        if ($currentStatus === InboxStatus::IN_PROGRESS) {
            return [$this->changeStatusToTreatedAction()];
        }

        if ($currentStatus === InboxStatus::TREATED) {
            return [$this->changeStatusToInProgressAction()];
        }

        return [
            $this->changeStatusToInProgressAction(),
            $this->changeStatusToTreatedAction(),
        ];
    }

    /**
     * Action to change status to IN_PROGRESS.
     */
    public function changeStatusToInProgressAction(): Action
    {
        return Action::make('changeStatusToInProgress')
            ->label(InboxStatus::IN_PROGRESS->label())
            ->icon('heroicon-o-arrow-path')
            ->color(InboxStatus::IN_PROGRESS->color())
            ->outlined()
            ->size('xs')
            ->requiresConfirmation()
            ->action(function () {
                if (! $this->selectedConversation) {
                    return;
                }

                $this->selectedConversation->status = InboxStatus::IN_PROGRESS;
                $this->selectedConversation->save();

                Notification::make()
                    ->title(__('filament-messenger::messages.status_updated'))
                    ->success()
                    ->send();

                $this->dispatch('tab-changed', InboxStatus::IN_PROGRESS->value);
            });
    }

    /**
     * Action to change status to TREATED.
     */
    public function changeStatusToTreatedAction(): Action
    {
        return Action::make('changeStatusToTreated')
            ->label(InboxStatus::TREATED->label())
            ->icon('heroicon-o-check-circle')
            ->color(InboxStatus::TREATED->color())
            ->outlined()
            ->size('xs')
            ->requiresConfirmation()
            ->action(function () {
                if (! $this->selectedConversation) {
                    return;
                }

                $this->selectedConversation->status = InboxStatus::TREATED;
                $this->selectedConversation->save();

                Notification::make()
                    ->title(__('filament-messenger::messages.status_updated'))
                    ->success()
                    ->send();

                $this->dispatch('tab-changed', InboxStatus::TREATED->value);
                $this->dispatch('refresh-inbox');
            });
    }

    /**
     * Determines if the message input is valid.
     */
    public function validateMessage(): bool
    {
        $rawData = $this->form->getRawState();
        return empty($rawData['message']);
    }

    /**
     * Render the messages view for the Livewire component.
     *
     * This method returns the view responsible for displaying
     * the messages interface, which includes the chat box and
     * input area for sending messages.
     */
    public function render(): Application|Factory|View|\Illuminate\View\View
    {
        return view('filament-messenger::livewire.messages.messages');
    }
}
