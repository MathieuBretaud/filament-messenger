<?php

namespace MathieuBretaud\FilamentMessenger\Livewire\Messages;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use MathieuBretaud\FilamentMessenger\Models\Message;

class Search extends Component
{
    public $search = '';

    public Collection $messages;

    /**
     * Set the initial state of the component.
     */
    public function mount(): void
    {
        $this->messages = collect();
    }

    /**
     * Clears the search input and refreshes the message list.
     *
     * This method is triggered when the modal is closed to reset
     * the search state, ensuring no search term is active.
     */
    #[On('close-modal')]
    public function clearSearch(): void
    {
        $this->search = '';
        $this->updatedSearch();
    }

    /**
     * Updates the messages list based on the current search input.
     *
     * This method trims the search input and checks if it's not empty.
     * If the search input contains text, it queries the Message model
     * to find messages that belong to the user's inbox and match the
     * search text. The resulting messages are limited to 5 results,
     * sorted by the latest, and stored in the messages collection.
     */
    public function updatedSearch(): void
    {
        $search = trim($this->search);
        $this->messages = collect();
        if (! empty($search)) {
            $this->messages = Message::query()
                ->with(['inbox'])
                ->whereIn('inbox_id', Auth::user()->allConversations()->select('id'))
                ->where('message', 'like', "%$search%")
                ->limit(5)
                ->latest()
                ->get();
        }
    }

    /**
     * Renders the search component.
     *
     * The component displays a list of messages that match the search query.
     */
    public function render(): Application | Factory | View | \Illuminate\View\View
    {
        return view('filament-messenger::livewire.messages.search', [
            'messages' => $this->messages,
        ]);
    }
}
