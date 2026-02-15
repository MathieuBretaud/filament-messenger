<?php

namespace MathieuBretaud\FilamentMessenger\Filament\Pages;

use MathieuBretaud\FilamentMessenger\Enums\InboxStatus;
use MathieuBretaud\FilamentMessenger\Models\Inbox;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Messages extends Page
{
    protected string $view = 'filament-messenger::filament.pages.messages';

    public ?Inbox $selectedConversation;

    /**
     * Get the slug for the messages page.
     *
     * @return string The slug with an optional ID placeholder.
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return config('filament-messenger.slug').'/{id?}';
    }

    /**
     * Determines whether the messages page should be registered in the navigation menu.
     *
     * Returns the value of the `filament-messenger.navigation.show_in_menu` config option.
     * Defaults to `true` if the option is not set.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-messenger.navigation.show_in_menu', true);
    }

    /**
     * Determines the navigation group for the messages page.
     *
     * Retrieves the value of the `filament-messenger.navigation.navigation_group`
     * configuration option, which defines the navigation group in the sidebar.
     * Defaults to `null` if the configuration option is not set.
     *
     * @return string|null The navigation group or null if not set.
     */
    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-messenger.navigation.navigation_group'));
    }

    /**
     * Get the label for the messages page navigation item.
     *
     * Retrieves the value of the `filament-messenger.navigation.navigation_label`
     * configuration option, which defines the label displayed in the sidebar.
     * Defaults to `'Messages'` if the option is not set.
     *
     * @return string The label for the messages page navigation item.
     */
    public static function getNavigationLabel(): string
    {
        return __(config('filament-messenger.navigation.navigation_label'));
    }

    /**
     * Returns the color of the navigation badge.
     *
     * Defaults to the value of the `filament.badge_color` config option.
     *
     * @return string|array|null The color of the navigation badge.
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return parent::getNavigationBadgeColor();
    }

    /**
     * Retrieves the new message count badge for the user in the sidebar.
     *
     * If the `filament-messenger.navigation.navigation_display_unread_messages_count`
     * config option is `true`, this method returns the count of new unread messages
     * (received messages that haven't been read OR in-progress messages where the
     * last message is not from the current user) for the authenticated user.
     * Otherwise, it returns the parent's navigation badge.
     *
     * @return string|null The new unread message count or null if not displayed.
     */
    public static function getNavigationBadge(): ?string
    {
        if (config('filament-messenger.navigation.navigation_display_unread_messages_count')) {
            $count = Auth::user()
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

            return $count > 0 ? (string) $count : null;
        }

        return parent::getNavigationBadge();
    }

    /**
     * Returns the icon for the navigation item.
     *
     * Retrieves the value of the `filament-messenger.navigation.navigation_icon`
     * configuration option, which defines the icon used in the sidebar.
     * Defaults to `null` if the configuration option is not set.
     *
     * @return string|Htmlable|null The navigation icon or null if not set.
     */
    public static function getNavigationIcon(): string|Htmlable|null
    {
        return config('filament-messenger.navigation.navigation_icon');
    }

    /**
     * Retrieves the navigation sort value for the messages page.
     *
     * Returns the value of the `filament-messenger.navigation.navigation_sort`
     * configuration option, which defines the navigation sort order in the sidebar.
     * Defaults to `null` if the option is not set.
     *
     * @return int|null The navigation sort value or null if not set.
     */
    public static function getNavigationSort(): ?int
    {
        return config('filament-messenger.navigation.navigation_sort');
    }

    /**
     * Mount the component.
     *
     * If an ID is provided, set the `selectedConversation` property to the matching Inbox model.
     *
     * @param  int|null  $id  The ID of the Inbox model to select.
     */
    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->selectedConversation = Inbox::with(['creator', 'recipient', 'patient'])
                ->findOrFail($id);
        }
    }

    /**
     * Gets the title of the messages page.
     *
     * Defaults to the value of the `filament-messenger.navigation.navigation_label` config option.
     *
     * @return string The title of the messages page.
     */
    public function getTitle(): string
    {
        return __(config('filament-messenger.navigation.navigation_label'));
    }

    /**
     * Get the maximum content width of the messages page.
     *
     * This method retrieves the maximum content width setting for the messages page
     * from the configuration file. It defaults to the value specified in the
     * `filament-messenger.max_content_width` configuration option.
     *
     * @return \Filament\Support\Enums\Width|string|null The maximum content width.
     */
    public function getMaxContentWidth(): Width|string|null
    {
        return config('filament-messenger.max_content_width');
    }

    /**
     * Gets the heading of the messages page.
     */
    public function getHeading(): string|Htmlable
    {
        return '';
    }
}
