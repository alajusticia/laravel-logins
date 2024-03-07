<?php

namespace ALajusticia\Logins\Notifications;

use ALajusticia\Logins\RequestContext;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use PeterColes\Countries\CountriesFacade;

class NewLogin extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly RequestContext $context
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $deviceType = match ($this->context->parser()->getDeviceType()) {
            'desktop', 'mobile', 'phone', 'tablet' => __('logins::notifications.new_login.device_types.' . $this->context->parser()->getDeviceType()),
            default => __('logins::notifications.new_login.device_types.unknown'),
        };

        $mailMessage = (new MailMessage)
            ->subject(__('logins::notifications.new_login.subject'))
            ->line(__('logins::notifications.new_login.title'))
            ->line(__('logins::notifications.new_login.review_information'))
            ->line('')
            ->line(__('logins::notifications.new_login.device_type') . $deviceType);

        if (! empty($this->context->parser()->getDevice())) {
            $mailMessage->line(__('logins::notifications.new_login.device_name') . $this->context->parser()->getDevice());
        }

        $mailMessage->line(__('logins::notifications.new_login.platform') . $this->context->parser()->getPlatform())
            ->line(__('logins::notifications.new_login.browser') . $this->context->parser()->getBrowser())
            ->line(__('logins::notifications.new_login.ip_address') . $this->context->ipAddress());

        if (! empty($this->context->location())) {
            if (! empty($this->context->location()->countryCode)) {
                $mailMessage->line(__('logins::notifications.new_login.country') . CountriesFacade::countryName($this->context->location()->countryCode, app()->getLocale()));
            } elseif (! empty($this->context->location()->countryName)) {
                $mailMessage->line(__('logins::notifications.new_login.country') . $this->context->location()->countryName);
            }
        }

        if ($securityPageRoute = Config::get('logins.security_page_route')) {
            $mailMessage->line('')
                ->line(__('logins::notifications.new_login.not_you'))
                ->action(__('logins::notifications.new_login.check_security'), route($securityPageRoute));
        }

        return $mailMessage;
    }
}
